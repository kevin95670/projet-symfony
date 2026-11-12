<?php

namespace App\Tests\Controller;

use App\Entity\Commande;
use App\Entity\Jeu;
use App\Enum\JeuStatut;
use App\Tests\Support\DonneesAchatTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Simule Stripe : on envoie au webhook un événement "paiement réussi"
 * signé avec le secret de test (STRIPE_WEBHOOK_SECRET dans .env.test).
 */
final class StripeWebhookTest extends WebTestCase
{
    use DonneesAchatTrait;

    // Reproduit la signature que Stripe ajoute dans l'en-tête Stripe-Signature
    private function signer(string $payload): string
    {
        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET');
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        return sprintf('t=%d,v1=%s', $timestamp, $signature);
    }

    private function evenementPaiement(int $panierId, int $acheteurId): string
    {
        return json_encode([
            'id' => 'evt_test_' . uniqid(),
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_' . uniqid(),
                    'object' => 'checkout.session',
                    'payment_intent' => 'pi_test_' . uniqid(),
                    'metadata' => [
                        'panier_id' => (string) $panierId,
                        'user_id' => (string) $acheteurId,
                    ],
                    'collected_information' => [
                        'shipping_details' => [
                            'name' => 'Test Utilisateur',
                            'address' => [
                                'line1' => '1 rue du Test',
                                'line2' => null,
                                'postal_code' => '75001',
                                'city' => 'Paris',
                                'country' => 'FR',
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function envoyerWebhook(object $client, string $payload, ?string $signature = null): void
    {
        $client->request('POST', '/stripe/webhook', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature ?? $this->signer($payload),
        ], $payload);
    }

    public function testSignatureInvalideRenvoieUneErreur400(): void
    {
        $client = static::createClient();

        $this->envoyerWebhook($client, '{"id":"evt_faux"}', 't=1,v1=signature-fausse');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPaiementCreeLaCommandeEtVendLeJeu(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $vendeur = $this->creerUtilisateur($em);
        $acheteur = $this->creerUtilisateur($em);
        $jeu = $this->creerJeu($em, $vendeur, '40.00');
        $panier = $this->creerPanier($em, $acheteur, [$jeu]);
        $em->flush();

        $payload = $this->evenementPaiement($panier->getId(), $acheteur->getId());
        $this->envoyerWebhook($client, $payload);

        $this->assertResponseIsSuccessful();
        $this->assertSame('OK', $client->getResponse()->getContent());

        // On repart d'une base "propre" en mémoire, comme le ferait une vraie requête Stripe
        $em->clear();
        $jeuRecharge = $em->getRepository(Jeu::class)->find($jeu->getId());
        $this->assertSame(JeuStatut::VENDU, $jeuRecharge->getStatut());
        $this->assertSame(1, $em->getRepository(Commande::class)->count([]));
    }

    public function testMemeEvenementRecuDeuxFoisNeCreeQu1Commande(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $vendeur = $this->creerUtilisateur($em);
        $acheteur = $this->creerUtilisateur($em);
        $jeu = $this->creerJeu($em, $vendeur);
        $panier = $this->creerPanier($em, $acheteur, [$jeu]);
        $em->flush();

        $payload = $this->evenementPaiement($panier->getId(), $acheteur->getId());

        $this->envoyerWebhook($client, $payload);
        $em->clear(); // Stripe renvoie le même événement : on repart d'une requête neuve
        $this->envoyerWebhook($client, $payload);

        $this->assertResponseIsSuccessful();
        $this->assertSame('Déjà traité', $client->getResponse()->getContent());
        $this->assertSame(1, $em->getRepository(Commande::class)->count([]));
    }
}