jQuery(document).ready(function($) {
    let selectedVariant = null;
    let selectedExtras = [];
    let selectedDuration = null;
    let selectedCondition = null;
    let selectedProductColor = null;
    let selectedFrameColor = null;
    let currentStripeLink = '#';
    let currentVariantImages = [];
    let currentMainImageIndex = 0;
    let currentCategoryId = null;
    let touchStartX = 0;
    let touchEndX = 0;
    let currentPrice = 0;

    // Get category ID from container
    const container = $('.federwiegen-container');
    if (container.length) {
        currentCategoryId = container.data('category-id');
    }

    // Initialize mobile sticky price bar
    initMobileStickyPrice();

    // Handle option selection
    $('.federwiegen-option').on('click', function() {
        const type = $(this).data('type');
        const id = $(this).data('id');

        // Prevent selection of unavailable options
        const available = $(this).data('available');
        if (available === false || available === 'false' || available === 0 || available === '0') {
            $('#federwiegen-rent-button').prop('disabled', true);
            $('.federwiegen-mobile-button').prop('disabled', true);
            $('#federwiegen-button-help').hide();
            $('#federwiegen-unavailable-help').show();
            return;
        }

        // Remove selection from same type (except extras which allow multiple)
        if (type !== 'extra') {
            $(`.federwiegen-option[data-type="${type}"]`).removeClass('selected');
            $(this).addClass('selected');
        } else {
            $(this).toggleClass('selected');
        }

        // Track interaction
        trackInteraction(type.replace('-', '_') + '_select', {
            variant_id: type === 'variant' ? id : selectedVariant,
            extra_ids: selectedExtras.join(','),
            duration_id: type === 'duration' ? id : selectedDuration,
            condition_id: type === 'condition' ? id : selectedCondition,
            product_color_id: type === 'product-color' ? id : selectedProductColor,
            frame_color_id: type === 'frame-color' ? id : selectedFrameColor
        });

        // Update selection variables
        if (type === 'variant') {
            selectedVariant = id;
            updateVariantImages($(this));
            updateVariantOptions(id);
        } else if (type === 'extra') {
            const index = selectedExtras.indexOf(id);
            if (index > -1) {
                selectedExtras.splice(index, 1);
            } else {
                selectedExtras.push(id);
            }
            updateExtraImage($(this));
        } else if (type === 'duration') {
            selectedDuration = id;
        } else if (type === 'condition') {
            selectedCondition = id;
        } else if (type === 'product-color') {
            selectedProductColor = id;
        } else if (type === 'frame-color') {
            selectedFrameColor = id;
        }

        // Update price and button state
        updatePriceAndButton();
    });

    // Handle rent button click
    $('#federwiegen-rent-button, .federwiegen-mobile-button').on('click', function() {
        if (!$(this).prop('disabled') && currentStripeLink !== '#') {
            // Track conversion
            trackInteraction('rent_button_click', {
                variant_id: selectedVariant,
                extra_ids: selectedExtras.join(','),
                duration_id: selectedDuration,
                condition_id: selectedCondition,
                product_color_id: selectedProductColor,
                frame_color_id: selectedFrameColor
            });
            
            // Submit order details
            submitOrder();
        }
    });

    // Handle thumbnail clicks
    $(document).on('click', '.federwiegen-thumbnail', function() {
        const index = $(this).data('index');
        showMainImage(index);
    });

    // Touch events for swipe navigation
    const mainImageContainer = document.getElementById('federwiegen-main-image-container');
    if (mainImageContainer) {
        mainImageContainer.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });

        mainImageContainer.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
    }

    function handleSwipe() {
        if (currentVariantImages.length <= 1) return;
        
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe left - next image
                const nextIndex = (currentMainImageIndex + 1) % currentVariantImages.length;
                showMainImage(nextIndex);
            } else {
                // Swipe right - previous image
                const prevIndex = currentMainImageIndex === 0 ? currentVariantImages.length - 1 : currentMainImageIndex - 1;
                showMainImage(prevIndex);
            }
        }
    }

    function updateVariantImages(variantOption) {
        const imagesData = variantOption.data('images');
        currentVariantImages = imagesData ? imagesData.filter(img => img && img.trim() !== '') : [];
        currentMainImageIndex = 0;
        
        const mainImageContainer = $('#federwiegen-main-image-container');
        const thumbnailsContainer = $('#federwiegen-thumbnails');
        
        if (currentVariantImages.length > 0) {
            // Show first image as main image
            showMainImage(0);
            
            // Create thumbnails if more than one image
            if (currentVariantImages.length > 1) {
                let thumbnailsHtml = '';
                currentVariantImages.forEach((imageUrl, index) => {
                    thumbnailsHtml += `
                        <div class="federwiegen-thumbnail ${index === 0 ? 'active' : ''}" data-index="${index}">
                            <img src="${imageUrl}" alt="Bild ${index + 1}">
                        </div>
                    `;
                });
                thumbnailsContainer.html(thumbnailsHtml).show();
                
                // Show swipe indicator only if multiple images and on mobile
                if (window.innerWidth <= 768) {
                    showSwipeIndicator();
                }
            } else {
                thumbnailsContainer.hide();
                hideSwipeIndicator();
            }
        } else {
            // Show default image or placeholder
            showDefaultImage();
            thumbnailsContainer.hide();
            hideSwipeIndicator();
        }
    }

    function showMainImage(index) {
        if (currentVariantImages[index]) {
            currentMainImageIndex = index;
            const mainImageContainer = $('#federwiegen-main-image-container');
            
            // Update main image with fade effect
            const imageHtml = `<img src="${currentVariantImages[index]}" alt="Federwiege" id="federwiegen-main-image" class="federwiegen-main-image federwiegen-fade-in">`;
            
            // Find and replace only the main image, keep extra overlay
            const existingMainImage = mainImageContainer.find('#federwiegen-main-image, #federwiegen-placeholder');
            if (existingMainImage.length > 0) {
                existingMainImage.fadeOut(200, function() {
                    $(this).replaceWith(imageHtml);
                    $('#federwiegen-main-image').fadeIn(200);
                });
            } else {
                mainImageContainer.prepend(imageHtml);
                $('#federwiegen-main-image').fadeIn(200);
            }
            
            // Update thumbnail active state
            $('.federwiegen-thumbnail').removeClass('active');
            $(`.federwiegen-thumbnail[data-index="${index}"]`).addClass('active');
        }
    }

    function showDefaultImage() {
        const mainImageContainer = $('#federwiegen-main-image-container');
        
        let imageHtml = '<div class="federwiegen-placeholder-image federwiegen-fade-in" id="federwiegen-placeholder">👶</div>';
        
        // Find and replace only the main image, keep extra overlay
        const existingMainImage = mainImageContainer.find('#federwiegen-main-image, #federwiegen-placeholder');
        if (existingMainImage.length > 0) {
            existingMainImage.replaceWith(imageHtml);
        } else {
            mainImageContainer.prepend(imageHtml);
        }
    }

    function showSwipeIndicator() {
        const mainImageContainer = $('#federwiegen-main-image-container');
        if (mainImageContainer.find('.federwiegen-swipe-indicator').length === 0) {
            mainImageContainer.append('<div class="federwiegen-swipe-indicator">Wischen für mehr Bilder</div>');
        }
    }

    function hideSwipeIndicator() {
        $('.federwiegen-swipe-indicator').remove();
    }

    function updateExtraImage(extraOption) {
        const extraOverlay = $('#federwiegen-extra-overlay');
        const extraImage = $('#federwiegen-extra-image');

        let imageUrl = '';
        if (extraOption && extraOption.hasClass('selected')) {
            imageUrl = extraOption.data('extra-image');
        }
        if (!imageUrl) {
            // fallback to first selected extra with image
            $('.federwiegen-option[data-type="extra"].selected').each(function () {
                const url = $(this).data('extra-image');
                if (url && url.trim() !== '') {
                    imageUrl = url;
                    return false;
                }
            });
        }

        if (imageUrl && imageUrl.trim() !== '') {
            extraImage.attr('src', imageUrl);
            extraOverlay.fadeIn(300);
        } else {
            extraOverlay.fadeOut(300);
        }
    }

    function updateVariantOptions(variantId) {
        // Get variant-specific options via AJAX
        $.ajax({
            url: federwiegen_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_variant_options',
                variant_id: variantId,
                nonce: federwiegen_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update conditions
                    updateOptionsDisplay('#condition-section', '.federwiegen-options.conditions', data.conditions, 'condition');
                    
                    // Update product colors
                    updateOptionsDisplay('#product-color-section', '.federwiegen-options.product-colors', data.product_colors, 'product-color');

                    // Update frame colors
                    updateOptionsDisplay('#frame-color-section', '.federwiegen-options.frame-colors', data.frame_colors, 'frame-color');

                    // Update extras
                    updateOptionsDisplay('#extras-section', '.federwiegen-options.extras', data.extras, 'extra');

                    // Reset selections for variant-specific options
                    selectedCondition = null;
                    selectedProductColor = null;
                    selectedFrameColor = null;
                    selectedExtras = [];
                    selectedDuration = null;
                    $('.federwiegen-options.durations .federwiegen-option').removeClass('selected');
                    updateExtraImage(null);
                    
                    updatePriceAndButton();
                }
            }
        });
    }

    function updateOptionsDisplay(sectionSelector, containerSelector, options, optionType) {
        const section = $(sectionSelector);
        const container = $(containerSelector);
        
        if (options.length === 0) {
            // Hide section if no options available
            section.hide();
            return;
        }
        
        // Show section and clear container
        section.show();
        container.empty();
        
        options.forEach(function(option) {
            let optionHtml = '';
            
            if (optionType === 'condition') {
                const badgeHtml = option.price_modifier != 0 ?
                    `<span class="federwiegen-condition-badge">${option.price_modifier > 0 ? '+' : ''}${Math.round(option.price_modifier * 100)}%</span>` : '';

                optionHtml = `
                    <div class="federwiegen-option ${option.available == 0 ? 'unavailable' : ''}" data-type="condition" data-id="${option.id}" data-available="${option.available == 0 ? 'false' : 'true'}">
                        <div class="federwiegen-option-content">
                            <div class="federwiegen-condition-header">
                                <span class="federwiegen-condition-name">${option.name}</span>
                                ${badgeHtml}
                            </div>
                            <p class="federwiegen-condition-info">${option.description}</p>
                        </div>
                        <div class="federwiegen-option-check">✓</div>
                    </div>
                `;
            } else if (optionType === 'product-color' || optionType === 'frame-color') {
                optionHtml = `
                    <div class="federwiegen-option ${option.available == 0 ? 'unavailable' : ''}" data-type="${optionType}" data-id="${option.id}" data-available="${option.available == 0 ? 'false' : 'true'}">
                        <div class="federwiegen-option-content">
                            <div class="federwiegen-color-display">
                                <div class="federwiegen-color-preview" style="background-color: ${option.color_code};"></div>
                                <span class="federwiegen-color-name">${option.name}</span>
                            </div>
                        </div>
                        <div class="federwiegen-option-check">✓</div>
                    </div>
                `;
            } else if (optionType === 'extra') {
                const priceHtml = option.price > 0 ? `+${parseFloat(option.price).toFixed(2).replace('.', ',')}€/Monat` : '';
                optionHtml = `
                    <div class="federwiegen-option ${option.available == 0 ? 'unavailable' : ''}" data-type="extra" data-id="${option.id}" data-extra-image="${option.image_url || ''}" data-available="${option.available == 0 ? 'false' : 'true'}">
                        <div class="federwiegen-option-content">
                            <span class="federwiegen-extra-name">${option.name}</span>
                            ${priceHtml ? `<div class="federwiegen-extra-price">${priceHtml}</div>` : ''}
                        </div>
                        <div class="federwiegen-option-check">✓</div>
                    </div>
                `;
            }
            
            container.append(optionHtml);
        });
        
        // Re-bind click events for new options
        container.find('.federwiegen-option').on('click', function() {
            const type = $(this).data('type');
            const id = $(this).data('id');

            const available = $(this).data('available');
            if (available === false || available === 'false' || available === 0 || available === '0') {
                $('#federwiegen-rent-button').prop('disabled', true);
                $('.federwiegen-mobile-button').prop('disabled', true);
                $('#federwiegen-button-help').hide();
                $('#federwiegen-unavailable-help').show();
                return;
            }

            if (type === 'extra') {
                $(this).toggleClass('selected');
                const index = selectedExtras.indexOf(id);
                if (index > -1) {
                    selectedExtras.splice(index, 1);
                } else {
                    selectedExtras.push(id);
                }
                updateExtraImage($(this));
            } else {
                $(`.federwiegen-option[data-type="${type}"]`).removeClass('selected');
                $(this).addClass('selected');

                if (type === 'condition') {
                    selectedCondition = id;
                } else if (type === 'product-color') {
                    selectedProductColor = id;
                } else if (type === 'frame-color') {
                    selectedFrameColor = id;
                }
            }
            
            // Track interaction
            trackInteraction(type.replace('-', '_') + '_select', {
                variant_id: selectedVariant,
                extra_ids: selectedExtras.join(','),
                duration_id: selectedDuration,
                condition_id: selectedCondition,
                product_color_id: selectedProductColor,
                frame_color_id: selectedFrameColor
            });
            
            updatePriceAndButton();
        });
    }

    function updatePriceAndButton() {
        // Check if all required selections are made
        const requiredSelections = [];
        if ($('.federwiegen-options.variants').length > 0) requiredSelections.push(selectedVariant);
        if ($('.federwiegen-options.extras').length > 0) requiredSelections.push(true);
        if ($('.federwiegen-options.durations').length > 0) requiredSelections.push(selectedDuration);
        
        // Check for visible optional sections
        if ($('#condition-section').is(':visible') && $('.federwiegen-options.conditions .federwiegen-option').length > 0) {
            requiredSelections.push(selectedCondition);
        }
        if ($('#product-color-section').is(':visible') && $('.federwiegen-options.product-colors .federwiegen-option').length > 0) {
            requiredSelections.push(selectedProductColor);
        }
        if ($('#frame-color-section').is(':visible') && $('.federwiegen-options.frame-colors .federwiegen-option').length > 0) {
            requiredSelections.push(selectedFrameColor);
        }
        
        const allSelected = requiredSelections.every(selection => selection !== null);
        
        if (allSelected) {
            // Show loading state
            $('#federwiegen-price-display').show();
            $('#federwiegen-final-price').text('Lädt...');

            // Make AJAX request
            $.ajax({
                url: federwiegen_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_product_price',
                    variant_id: selectedVariant,
                    extra_ids: selectedExtras.join(','),
                    duration_id: selectedDuration,
                    condition_id: selectedCondition,
                    product_color_id: selectedProductColor,
                    frame_color_id: selectedFrameColor,
                    nonce: federwiegen_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        currentPrice = data.final_price;
                        
                        // Update price display
                        $('#federwiegen-final-price').text(formatPrice(data.final_price) + '€');
                        
                        if (data.discount > 0) {
                            $('#federwiegen-original-price').text(formatPrice(data.base_price) + '€').show();
                            const savings = data.base_price - data.final_price;
                            $('#federwiegen-savings').text(`Sie sparen ${formatPrice(savings)}€ pro Monat!`).show();
                        } else {
                            $('#federwiegen-original-price').hide();
                            $('#federwiegen-savings').hide();
                        }

                        // Update button based on availability
                        currentStripeLink = data.stripe_link;
                        const isAvailable = data.available !== false;
                        
                        $('#federwiegen-rent-button').prop('disabled', !isAvailable);
                        $('.federwiegen-mobile-button').prop('disabled', !isAvailable);
                        
                        if (isAvailable) {
                            $('#federwiegen-button-help').hide();
                            $('#federwiegen-unavailable-help').hide();
                        } else {
                            $('#federwiegen-button-help').hide();
                            $('#federwiegen-unavailable-help').show();
                            if (data.availability_note) {
                                $('#federwiegen-unavailable-help').text(data.availability_note);
                            }
                        }
                        
                        // Update mobile sticky price
                        updateMobileStickyPrice(data.final_price, isAvailable);
                    }
                },
                error: function() {
                    $('#federwiegen-final-price').text('Fehler');
                }
            });
        } else {
            // Hide price display and disable button
            $('#federwiegen-price-display').hide();
            $('#federwiegen-rent-button').prop('disabled', true);
            $('.federwiegen-mobile-button').prop('disabled', true);
            $('#federwiegen-button-help').show();
            $('#federwiegen-unavailable-help').hide();
            currentStripeLink = '#';
            currentPrice = 0;
            
            // Hide mobile sticky price
            hideMobileStickyPrice();
        }
    }

    function initMobileStickyPrice() {
        if (window.innerWidth <= 768) {
            // Determine button label from main button
            const mainLabel = $('#federwiegen-rent-button span').text().trim() || 'Jetzt Mieten';

            // Create mobile sticky price bar
            const stickyHtml = `
                <div class="federwiegen-mobile-sticky-price" id="mobile-sticky-price">
                    <div class="federwiegen-mobile-sticky-content">
                        <div class="federwiegen-mobile-price-info">
                            <div class="federwiegen-mobile-price-label">Monatlicher Mietpreis</div>
                            <div class="federwiegen-mobile-price-value" id="mobile-price-value">0,00€</div>
                        </div>
                        <button class="federwiegen-mobile-button" disabled>
                            ${mainLabel}
                        </button>
                    </div>
                </div>
            `;
            $('body').append(stickyHtml);
            
            // Show/hide based on scroll position
            $(window).scroll(function() {
                const scrollTop = $(this).scrollTop();
                const priceDisplay = $('#federwiegen-price-display');
                
                if (priceDisplay.is(':visible') && currentPrice > 0) {
                    const priceDisplayTop = priceDisplay.offset().top;
                    const priceDisplayBottom = priceDisplayTop + priceDisplay.outerHeight();
                    
                    // Show sticky price when price display is scrolled out of view (above viewport)
                    // Keep it visible for the entire page - never hide it once shown
                    const priceOutOfView = scrollTop > priceDisplayBottom;
                    
                    if (priceOutOfView) {
                        showMobileStickyPrice();
                    } else {
                        hideMobileStickyPrice();
                    }
                } else {
                    hideMobileStickyPrice();
                }
            });
        }
    }

    function updateMobileStickyPrice(price, isAvailable) {
        if (window.innerWidth <= 768) {
            $('#mobile-price-value').text(formatPrice(price) + '€');
            $('.federwiegen-mobile-button').prop('disabled', !isAvailable);
        }
    }

    function showMobileStickyPrice() {
        if (window.innerWidth <= 768) {
            $('#mobile-sticky-price').addClass('show');
        }
    }

    function hideMobileStickyPrice() {
        $('#mobile-sticky-price').removeClass('show');
    }

    function submitOrder() {
        const finalPrice = currentPrice;
        
        $.ajax({
            url: federwiegen_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_order',
                category_id: currentCategoryId,
                variant_id: selectedVariant,
                extra_ids: selectedExtras.join(','),
                duration_id: selectedDuration,
                condition_id: selectedCondition,
                product_color_id: selectedProductColor,
                frame_color_id: selectedFrameColor,
                final_price: finalPrice,
                stripe_link: currentStripeLink,
                nonce: federwiegen_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to Stripe
                    window.open(currentStripeLink, '_blank');
                }
            }
        });
    }

    function trackInteraction(eventType, data = {}) {
        if (!currentCategoryId) return;
        
        $.ajax({
            url: federwiegen_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'track_interaction',
                category_id: currentCategoryId,
                event_type: eventType,
                variant_id: data.variant_id || null,
                extra_ids: data.extra_ids || null,
                duration_id: data.duration_id || null,
                condition_id: data.condition_id || null,
                product_color_id: data.product_color_id || null,
                frame_color_id: data.frame_color_id || null,
                nonce: federwiegen_ajax.nonce
            }
        });
    }

    function formatPrice(price) {
        return parseFloat(price).toFixed(2).replace('.', ',');
    }
});
