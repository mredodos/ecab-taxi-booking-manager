
jQuery(document).ready(function($) {
    // Initialize all endpoint sections as collapsed
    $('.endpoint-section .endpoint-details').hide();
    
    // Handle click on endpoint section headers
    $('.endpoint-section h3').on('click', function() {
        var $section = $(this).closest('.endpoint-section');
        var $details = $section.find('.endpoint-details');
        
        // Toggle the active class
        $section.toggleClass('active');
        
        // Slide toggle the content
        $details.slideToggle(300);
    });

    // Show the first endpoint section by default
    $('.endpoint-section:first').addClass('active').find('.endpoint-details').show();

    // Add copy buttons to all code blocks
    $('code, pre').each(function() {
        var $code = $(this);
        var $copyButton = $('<button/>')
            .addClass('copy-button')
            .text('Copy')
            .css({
                position: 'absolute',
                right: '10px',
                top: '10px',
                padding: '4px 8px',
                background: '#f0f0f1',
                border: '1px solid #c3c4c7',
                borderRadius: '3px',
                cursor: 'pointer',
                fontSize: '12px'
            });

        // Add relative positioning to the code block's parent
        $code.parent().css('position', 'relative');
        
        // Add the copy button
        $code.parent().append($copyButton);

        // Handle copy button click
        $copyButton.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Get the text to copy
            var textToCopy = $code.text();

            // Create a temporary textarea element
            var $temp = $('<textarea>')
                .val(textToCopy)
                .appendTo('body')
                .select();

            try {
                // Copy the text
                document.execCommand('copy');
                
                // Update button text temporarily
                var $button = $(this);
                $button.text('Copied!');
                $button.css('background', '#00a32a').css('color', 'white');
                
                // Reset button text after 2 seconds
                setTimeout(function() {
                    $button.text('Copy');
                    $button.css('background', '#f0f0f1').css('color', 'initial');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy text:', err);
            }

            // Remove the temporary textarea
            $temp.remove();
        });
    });

    // Add HTTP method badges
    $('.endpoint-details').each(function() {
        var $endpoint = $(this);
        var endpointText = $endpoint.find('p:contains("Endpoint:")').text();
        
        // Extract HTTP method from the endpoint text
        var method = endpointText.match(/(GET|POST|PUT|DELETE)/);
        
        if (method) {
            var methodClass = method[1].toLowerCase();
            var $badge = $('<span/>')
                .addClass('http-method ' + methodClass)
                .text(method[1]);
            
            $endpoint.find('p:contains("Endpoint:")').prepend($badge);
        }
    });

    // Add status code badges
    $('.example-response').each(function() {
        var $response = $(this);
        var responseText = $response.text();
        
        // Look for common status codes
        var statusCodes = responseText.match(/(200|201|400|401|404)/);
        
        if (statusCodes) {
            var $badge = $('<span/>')
                .addClass('status-code status-' + statusCodes[1])
                .text(statusCodes[1]);
            
            $response.find('h4').append($badge);
        }
    });

    // Add required field indicators
    $('.parameters tbody tr').each(function() {
        var $row = $(this);
        var isRequired = $row.find('td:eq(2)').text().toLowerCase() === 'yes';
        
        if (isRequired) {
            $row.find('td:first').append(
                $('<span/>')
                    .addClass('required')
                    .text(' *')
                    .attr('title', 'Required field')
            );
        }
    });

    // Add smooth scroll to anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 50
            }, 500);
        }
    });

    // Add search functionality
    var $searchInput = $('<input/>')
        .attr({
            type: 'text',
            placeholder: 'Search endpoints...',
            id: 'endpoint-search'
        })
        .css({
            width: '100%',
            padding: '8px',
            marginBottom: '20px',
            border: '1px solid #ddd',
            borderRadius: '4px'
        });

    $('.mptbm-api-endpoints').prepend($searchInput);

    $('#endpoint-search').on('input', function() {
        var searchText = $(this).val().toLowerCase();
        
        $('.endpoint-section').each(function() {
            var $section = $(this);
            var sectionText = $section.text().toLowerCase();
            
            if (sectionText.includes(searchText)) {
                $section.show();
            } else {
                $section.hide();
            }
        });
    });
});
