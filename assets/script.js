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
    let currentProductColorImage = null;
    let currentFrameColorImage = null;
    let currentCategoryId = null;
    let touchStartX = 0;
    let touchEndX = 0;
    let currentPrice = 0;
    let colorNotificationTimeout = null;

    // Get category ID from container
    const container = $('.federwiegen-container');
    if (container.length) {
        currentCategoryId = container.data('category-id');
    }

    // Remove old inline color labels if they exist
    $('.federwiegen-color-name').remove();

    // Initialize mobile sticky price bar
    initMobileStickyPrice();

    // Handle option selection
    $('.federwiegen-option').on('click', function() {
        const type = $(this).data('type');
        const id = $(this).data('id');

        // Prevent selection of unavailable options
        const available = $(this).data('available');
        if (available === false || available === 'false' || available === 0 || available === '0') {
            if (type === 'variant') {
                selectedVariant = id;
            } else if (type === 'product-color') {
                selectedProductColor = id;
            } else if (type === 'frame-color') {
                selectedFrameColor = id;
            } else if (type === 'condition') {
                selectedCondition = id;
            } else if (type === 'extra') {
                selectedExtras = [id];
            }
            $(`.federwiegen-option[data-type="${type}"]`).removeClass('selected');
            $('#federwiegen-rent-button').prop('disabled', true);
            $('.federwiegen-mobile-button').prop('disabled', true);
            $('#federwiegen-button-help').hide();
            $('#federwiegen-unavailable-help').show();
            $('#federwiegen-notify').show();
            $('.federwiegen-notify-form').show();
            $('#federwiegen-notify-success').hide();
            $('#federwiegen-availability-wrapper').show();
            $('#federwiegen-availability-status').addClass('unavailable').removeClass('available');
            $('#federwiegen-availability-status .status-text').text('Nicht auf Lager');
            $('#federwiegen-delivery-box').hide();
            scrollToNotify();
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

            // Reset selections when switching variants so the rent button
            // becomes inactive immediately
            selectedCondition = null;
            selectedProductColor = null;
            selectedFrameColor = null;
            selectedExtras = [];
            selectedDuration = null;

            $('.federwiegen-option[data-type="condition"]').removeClass('selected');
            $('.federwiegen-option[data-type="product-color"]').removeClass('selected');
            $('.federwiegen-option[data-type="frame-color"]').removeClass('selected');
            $('.federwiegen-option[data-type="extra"]').removeClass('selected');
            $('.federwiegen-option[data-type="duration"]').removeClass('selected');

            updateExtraImage(null);
            updateColorImage(null);

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
            $('#selected-product-color-name').text($(this).data('color-name'));
            updateColorImage($(this));
        } else if (type === 'frame-color') {
            selectedFrameColor = id;
            $('#selected-frame-color-name').text($(this).data('color-name'));
            updateColorImage($(this));
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

            // Open popup immediately to avoid blockers
            const stripeWindow = window.open('', '_blank');

            // Submit order details and redirect when done
            submitOrder(stripeWindow);
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

    function updateVariantImages(variantOption, activeIndex = 0) {
        const imagesData = variantOption.data('images');
        currentVariantImages = imagesData ? imagesData.filter(img => img && img.trim() !== '') : [];

        if (currentProductColorImage) {
            currentVariantImages.push(currentProductColorImage);
        }
        if (currentFrameColorImage) {
            currentVariantImages.push(currentFrameColorImage);
        }

        currentMainImageIndex = Math.min(activeIndex, currentVariantImages.length - 1);

        rebuildImageGallery();
    }

    function rebuildImageGallery() {
        const mainImageContainer = $('#federwiegen-main-image-container');
        const thumbnailsContainer = $('#federwiegen-thumbnails');

        if (currentVariantImages.length > 0) {
            showMainImage(currentMainImageIndex);

            if (currentVariantImages.length > 1) {
                let thumbnailsHtml = '';
                currentVariantImages.forEach((imageUrl, index) => {
                    thumbnailsHtml += `
                        <div class="federwiegen-thumbnail ${index === currentMainImageIndex ? 'active' : ''}" data-index="${index}">
                            <img src="${imageUrl}" alt="Bild ${index + 1}">
                        </div>
                    `;
                });
                thumbnailsContainer.html(thumbnailsHtml).show();

                if (window.innerWidth <= 768) {
                    showSwipeIndicator();
                }
            } else {
                thumbnailsContainer.hide();
                hideSwipeIndicator();
            }
        } else {
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

    function updateColorImage(colorOption) {
        if (!colorOption) {
            currentProductColorImage = null;
            currentFrameColorImage = null;
        } else if (!selectedVariant) {
            return;
        } else {
            const imageUrl = colorOption.data('color-image') || '';
            const type = colorOption.data('type');
            if (type === 'product-color') {
                currentProductColorImage = imageUrl.trim() !== '' ? imageUrl : null;
            } else if (type === 'frame-color') {
                currentFrameColorImage = imageUrl.trim() !== '' ? imageUrl : null;
            }
        }

        const variantOption = $('.federwiegen-option[data-type="variant"].selected');
        if (variantOption.length) {
            const baseImages = variantOption.data('images');
            const variantImages = baseImages ? baseImages.filter(img => img && img.trim() !== '') : [];
            let index = 0;
            if (colorOption) {
                const type = colorOption.data('type');
                index = variantImages.length;
                if (type === 'frame-color' && currentProductColorImage) index += 1;
            }
            updateVariantImages(variantOption, index);

            if (colorOption && (currentProductColorImage || currentFrameColorImage)) {
                showGalleryNotification();
            }
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
                    $('#selected-product-color-name').text('');
                    $('#selected-frame-color-name').text('');
                    selectedExtras = [];
                    selectedDuration = null;
                    $('.federwiegen-options.durations .federwiegen-option').removeClass('selected');
                    updateExtraImage(null);
                    updateColorImage(null);

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
                    <div class="federwiegen-option ${option.available == 0 ? 'unavailable' : ''}" data-type="${optionType}" data-id="${option.id}" data-available="${option.available == 0 ? 'false' : 'true'}" data-color-name="${option.name}" data-color-image="${option.image_url || ''}">
                        <div class="federwiegen-option-content">
                            <div class="federwiegen-color-display">
                                <div class="federwiegen-color-preview" style="background-color: ${option.color_code};"></div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (optionType === 'extra') {
                const priceSuffix = federwiegen_ajax.price_period === 'month' ? '/Monat' : '';
                const priceHtml = option.price > 0 ? `+${parseFloat(option.price).toFixed(2).replace('.', ',')}€${priceSuffix}` : '';
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

        // Remove any leftover inline color names
        container.find('.federwiegen-color-name').remove();

        // Re-bind click events for new options
        container.find('.federwiegen-option').on('click', function() {
            const type = $(this).data('type');
            const id = $(this).data('id');

            const available = $(this).data('available');
            if (available === false || available === 'false' || available === 0 || available === '0') {
                if (type === 'product-color') {
                    selectedProductColor = id;
                } else if (type === 'frame-color') {
                    selectedFrameColor = id;
                } else if (type === 'condition') {
                    selectedCondition = id;
                } else if (type === 'extra') {
                    selectedExtras = [id];
                }
                $(`.federwiegen-option[data-type="${type}"]`).removeClass('selected');
                $('#federwiegen-rent-button').prop('disabled', true);
                $('.federwiegen-mobile-button').prop('disabled', true);
                $('#federwiegen-button-help').hide();
                $('#federwiegen-unavailable-help').show();
                $('#federwiegen-notify').show();
                $('.federwiegen-notify-form').show();
                $('#federwiegen-notify-success').hide();
                $('#federwiegen-availability-wrapper').show();
                $('#federwiegen-availability-status').addClass('unavailable').removeClass('available');
                $('#federwiegen-availability-status .status-text').text('Nicht auf Lager');
                $('#federwiegen-delivery-box').hide();
                scrollToNotify();
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
                    $('#selected-product-color-name').text($(this).data('color-name'));
                    updateColorImage($(this));
                } else if (type === 'frame-color') {
                    selectedFrameColor = id;
                    $('#selected-frame-color-name').text($(this).data('color-name'));
                    updateColorImage($(this));
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
                            const saveSuffix = federwiegen_ajax.price_period === 'month' ? ' pro Monat!' : '!';
                            $('#federwiegen-savings').text(`Sie sparen ${formatPrice(savings)}€${saveSuffix}`).show();
                        } else {
                            $('#federwiegen-original-price').hide();
                            $('#federwiegen-savings').hide();
                        }

                        // Update button based on availability
                        currentStripeLink = data.stripe_link;
                        const isAvailable = data.available !== false;

                        $('#federwiegen-rent-button').prop('disabled', !isAvailable);
                        $('.federwiegen-mobile-button').prop('disabled', !isAvailable);

                        $('#federwiegen-availability-wrapper').show();
                        if (isAvailable) {
                            $('#federwiegen-availability-status').removeClass('unavailable').addClass('available');
                            $('#federwiegen-availability-status .status-text').text('Sofort verfügbar');
                            $('#federwiegen-delivery-time').text(data.delivery_time || '');
                            $('#federwiegen-delivery-box').show();
                        } else {
                            $('#federwiegen-availability-status').addClass('unavailable').removeClass('available');
                            $('#federwiegen-availability-status .status-text').text('Nicht auf Lager');
                            $('#federwiegen-delivery-box').hide();
                        }

                        if (isAvailable) {
                            $('#federwiegen-button-help').hide();
                            $('#federwiegen-unavailable-help').hide();
                            $('#federwiegen-notify').hide();
                            $('.federwiegen-notify-form').show();
                            $('#federwiegen-notify-success').hide();
                        } else {
                            $('#federwiegen-button-help').hide();
                            $('#federwiegen-unavailable-help').show();
                            $('#federwiegen-notify').show();
                            $('.federwiegen-notify-form').show();
                            $('#federwiegen-notify-success').hide();
                            if (data.availability_note) {
                                $('#federwiegen-unavailable-help').text(data.availability_note);
                            }
                            scrollToNotify();
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
            $('#federwiegen-notify').hide();
            $('#federwiegen-notify-success').hide();
            $('.federwiegen-notify-form').show();
            currentStripeLink = '#';
            currentPrice = 0;

            $('#federwiegen-availability-wrapper').hide();
            
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
                            <div class="federwiegen-mobile-price-label">${federwiegen_ajax.price_label}</div>
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
            const suffix = federwiegen_ajax.price_period === 'month' ? '/Monat' : '';
            $('#mobile-price-value').text(formatPrice(price) + '€' + suffix);
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

    function showGalleryNotification() {
        if (window.innerWidth > 768) return;

        let toast = $('#federwiegen-color-toast');
        if (!toast.length) {
            toast = $('<div id="federwiegen-color-toast" class="federwiegen-color-toast">Ein Bild zur Farbe wurde der Produktgalerie hinzugefügt</div>');
            $('body').append(toast);
            toast.on('click', function() {
                const gallery = $('#federwiegen-image-gallery');
                if (gallery.length) {
                    $('html, body').animate({ scrollTop: gallery.offset().top - 100 }, 500);
                }
            });
        }

        toast.stop(true, true).fadeIn(200);
        clearTimeout(colorNotificationTimeout);
        colorNotificationTimeout = setTimeout(function() {
            toast.fadeOut(200);
        }, 3000);
    }

    function submitOrder(stripeWindow) {
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
                    // Redirect to Stripe using previously opened window
                    if (stripeWindow) {
                        stripeWindow.location = currentStripeLink;
                    } else {
                        window.open(currentStripeLink, '_blank');
                    }
                } else if (stripeWindow) {
                    stripeWindow.close();
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

    function scrollToNotify() {
        const target = $('#federwiegen-notify');
        if (target.length) {
            $('html, body').animate({ scrollTop: target.offset().top - 100 }, 500);
        }
    }

    // Notify when product becomes available
    $('#federwiegen-notify-submit').on('click', function(e) {
        e.preventDefault();
        const email = $('#federwiegen-notify-email').val().trim();
        if (!email) return;

        $.ajax({
            url: federwiegen_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'notify_availability',
                email: email,
                category_id: currentCategoryId,
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
                    $('.federwiegen-notify-form').hide();
                    $('#federwiegen-notify-success').show();
                }
            }
        });
    });
});
