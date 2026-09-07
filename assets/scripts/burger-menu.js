$(document).ready(function() {
    $('#burger').off('click').on('click', function(e) {
        e.stopPropagation();
        $('#mobile-menu').toggleClass('hidden');
    });

    $(document).off('click.menu').on('click.menu', function(e) {
        if (!$('#mobile-menu').has(e.target).length && !$('#burger').is(e.target)) {
            $('#mobile-menu').addClass('hidden');
        }
    });
});