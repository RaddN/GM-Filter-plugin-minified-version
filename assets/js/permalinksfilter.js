jQuery(document).ready(function($) {
    let advancesettings;
    let wcapf_options ;
    let dapfforwc_front_page_slug;
    let selectedValesbyuser = store_selected_values();
    if (typeof dapfforwc_data !== 'undefined' && dapfforwc_data.dapfforwc_front_page_slug) {
        dapfforwc_front_page_slug = dapfforwc_data.dapfforwc_front_page_slug;
    }
    if (typeof dapfforwc_data !== 'undefined' && dapfforwc_data.wcapf_options) {
        wcapf_options = dapfforwc_data.wcapf_options;
    }
    if (typeof dapfforwc_data !== 'undefined' && dapfforwc_data.dapfforwc_advance_settings) {
        advancesettings = dapfforwc_data.dapfforwc_advance_settings;
    }
    const $productFilter = $('#product-filter');
    const defaultFilterValues = normalizeFilterValues($productFilter.attr('data-default_filters') || $productFilter.data('default_filters') || []);
    var rfilterbuttonsId = $('.rfilterbuttons').first().attr('id');
    var orderby;
    $('.dapfforwc-active-filters').parent().addClass('dapfforwc-filter-toolbar');
    ensureMobileFilterTabs();
    syncCheckboxSelections();
    updateSingleFilterNav();
    updateActiveFilterNav();
    $('#product-filter').on('click', '.dapfforwc-filter-toggle', function(event) {
        event.preventDefault();
        event.stopPropagation();

        const $button = $(this);
        const $group = $button.closest('.filter-group');

        if (isMobile()) {
            toggleMobileFilterGroup($group);
            return;
        }

        const isCollapsed = !$group.hasClass('is-collapsed');

        $group.toggleClass('is-collapsed', isCollapsed);
        $button.attr('aria-expanded', isCollapsed ? 'false' : 'true');
    });

    $('#product-filter').on('click', '.dapfforwc-view-all', function(event) {
        event.preventDefault();

        const $button = $(this);
        const $group = $button.closest('.filter-group');
        const isExpanded = !$group.hasClass('is-expanded');
        const collapsedText = $button.data('collapsed-text') || $button.find('span').text();
        const expandedText = $button.data('expanded-text') || 'Show less';

        $group.toggleClass('is-expanded', isExpanded);
        $button.find('span').text(isExpanded ? expandedText : collapsedText);
    });
    
    // Initialize filters and handle changes
    
    $('#product-filter, .rfilterbuttons').on('change','.filter-checkbox', handleFilterChange);
    $('#product-filter, .rfilterbuttons').on('submit', handleFilterChange);
    $(document).on('click', '.dapfforwc-single-filter-arrow', function(event) {
        event.preventDefault();
        event.stopPropagation();

        const $button = $(this);
        const $nav = $button.closest('.dapfforwc-single-filter-nav');
        const list = $nav.find('.rfilterbuttons ul').get(0);

        if (!list) {
            return;
        }

        const direction = $button.hasClass('dapfforwc-single-filter-arrow-prev') ? -1 : 1;
        list.scrollBy({
            left: direction * Math.max(160, Math.round(list.clientWidth * 0.75)),
            behavior: 'smooth'
        });
        setTimeout(updateSingleFilterNav, 250);
        setTimeout(updateSingleFilterNav, 600);
    });
    $(document).on('click', '.dapfforwc-active-filter-arrow', function(event) {
        event.preventDefault();
        event.stopPropagation();

        const $button = $(this);
        const list = $button.closest('.dapfforwc-active-filters').find('.dapfforwc-active-filter-list').get(0);

        if (!list) {
            return;
        }

        const direction = $button.hasClass('dapfforwc-active-filter-arrow-prev') ? -1 : 1;
        list.scrollBy({
            left: direction * Math.max(160, Math.round(list.clientWidth * 0.75)),
            behavior: 'smooth'
        });
        setTimeout(updateActiveFilterNav, 250);
        setTimeout(updateActiveFilterNav, 600);
    });
    $(document).on('scroll', '.rfilterbuttons ul', updateSingleFilterNav);
    $(document).on('scroll', '.dapfforwc-active-filter-list', updateActiveFilterNav);
    $(window).on('resize', function() {
        updateSingleFilterNav();
        updateActiveFilterNav();
        ensureMobileFilterTabs();
        syncMobileFilterGroupState();
    });
    $('.dapfforwc-active-filters, .rfilterselected').on('click', '.dapfforwc-active-filter-remove', function(event) {
        event.preventDefault();
        event.stopPropagation();

        setFilterValueChecked($(this).data('filter-value'), false);
        refreshFiltersAfterSelectionChange();
    });
    $('.dapfforwc-active-filters, .rfilterselected').on('click', '.dapfforwc-active-filter-clear', function(event) {
        event.preventDefault();
        event.stopPropagation();

        store_selected_values().filter(shouldShowFilterInUrl).forEach(function(value) {
            setFilterValueChecked(value, false);
        });
        refreshFiltersAfterSelectionChange();
    });
    $('.woocommerce-ordering select').on('change', function(event) {
        // Prevent the default form submission and page reload
        event.preventDefault();

        // Get the selected value
        orderby = $(this).val();
        fetchFilteredProducts();

    });

    // Prevent form submission on pressing Enter
    $('.woocommerce-ordering').on('submit', function(event) {
        event.preventDefault();
    });
  

    var rfiltercurrentUrl = window.location.href;
    var path = window.location.pathname;
    var shortcodeCurrentPage = $productFilter.data('current_page_slug') || '';
    var currentPage = shortcodeCurrentPage || (path==="/"? dapfforwc_front_page_slug : path.replace(/^\/|\/$/g, ''));
    var filterBaseUrl = $productFilter.data('base_url') || window.location.href;
    const pageRouteValues = normalizeFilterValues(currentPage);
    const urlExcludedFilterValues = new Set(defaultFilterValues.concat(pageRouteValues));
    if (window.location.search.indexOf('preview=true') !== -1) {
        filterBaseUrl = window.location.href;
    }
    rfiltercurrentUrl = rfiltercurrentUrl.split('?')[0];
    const urlParams = new URLSearchParams(window.location.search);
    const gmfilter = urlParams.get('filters');
    const pathFilter = getPathFilters();
    
    if (typeof dapfforwc_data !== 'undefined' && dapfforwc_data.dapfforwc_slug) {
        
        const slugArray = dapfforwc_data.dapfforwc_slug.split('/').filter(value => value !== '');
        if (slugArray.length > 0) {
            const filtersString = slugArray.join(',');
            applyFiltersFromUrl(filtersString);
            updateUrlFilters();
        }
    }else if(gmfilter){
        const slugtoArray = gmfilter.split('/').filter(value => value !== '');
        if (slugtoArray.length > 0) {
            const filtersString = slugtoArray.join(',');
            applyFiltersFromUrl(filtersString);
            updateUrlFilters();
        }
    }
    else if(pathFilter){
        const slugtoArray = pathFilter.split('/').filter(value => value !== '');
        if (slugtoArray.length > 0) {
            const filtersString = slugtoArray.join(',');
            applyFiltersFromUrl(filtersString);
            updateUrlFilters();
        }
    }
    else if (anyFilterSelected()) {
        fetchFilteredProducts();
    }
    
    function handleFilterChange(e) {
        e.preventDefault();
        const checkbox = $(this); // Reference the checkbox that triggered the change
        const value = checkbox.val();
        const isChecked = checkbox.is(':checked');
        
        // Synchronize checkboxes with the same value
        $('.filter-checkbox').each(function() {
            if ($(this).val() === value) {
                $(this).prop('checked', isChecked);
            }
        });

        refreshFiltersAfterSelectionChange();
    }

    function refreshFiltersAfterSelectionChange() {
        selectedValesbyuser = store_selected_values();
        updateUrlFilters();
        selectedFilterShowProductTop();
        syncSingleFilterState();
        updateSingleFilterNav();

        if (!anyFilterSelected()) {
            return location.reload();
        }

        $('#roverlay').show();
        $('#loader').show();
        fetchFilteredProducts();
    }
    function store_selected_values() {
    let selectedValues = [];

    // Get selected values from checkboxes and radio buttons
    selectedValues = selectedValues.concat(
        $('#product-filter input:checked').map(function() {
            return $(this).val();
        }).get()
    );

    // Get selected values from select elements
    $('#product-filter select').each(function() {
        const values = $(this).val();
        if (values) { // Check if a value is selected
            selectedValues = selectedValues.concat(values);
        }
    });

    return Array.from(new Set(selectedValues.filter(Boolean)));
}


function selectfromurl(){
    let urlvalues = getUrlFilterValues();
urlvalues.forEach(value => {
    // Check the input checkbox
    if ($(`input[value="${value}"]`).length) {
        $(`input[value="${value}"]`).attr('checked', true);
    } else if ($(`select option[value="${value}"]`).length) {
        // If no input found, check dropdown option
        $(`select option[value="${value}"]`).prop('selected', true);
    }
});
}
selectfromurl();

function anyFilterSelected() {
    const inputchecked = $('#product-filter input:checked').length > 0;
    const selectSelected = $('#product-filter select').filter(function() { return this.value; }).length > 0;
    const textInputSelected = $('#product-filter input[type="text"]').filter(function() { return this.value.trim() !== ""; }).length > 0;
    const numberInputSelected = $('#product-filter input[type="number"]').filter(function() { return this.value.trim() !== ""; }).length > 0;
    const range = $('#product-filter input[type="range"]').filter(function() { return this.value.trim() !== ""; }).length > 0;

    return inputchecked || selectSelected || textInputSelected || numberInputSelected || range;
}
    let product_selector = advancesettings ? advancesettings["product_selector"] ?? 'ul.products':'ul.products';
    let pagination_selector = advancesettings ? advancesettings["pagination_selector"] ?? 'ul.page-numbers' : 'ul.page-numbers';
    let productSelector_shortcode = $('#product-filter').data('product_selector');
    let paginationSelector_shortcode = $('#product-filter').data('pagination_selector');
    
    function fetchFilteredProducts(page = 1) {
        selectfromurl();
        selectedValesbyuser = store_selected_values();
        $.post(dapfforwc_ajax.ajax_url, gatherFormData() +  `&selectedvalues=${selectedValesbyuser}&orderby=${orderby}&paged=${page}&action=dapfforwc_filter_products`, function(response) {
            $('#roverlay').hide();
            $('#loader').hide();
            if (response.success) {
                $(productSelector_shortcode ?? product_selector).html(response.data.products);
                $('.woocommerce-result-count').text(`${response.data.total_product_fetch} results found`);
                if(wcapf_options["update_filter_options"]==="on"){
                $('#product-filter div').remove();
                $("form#product-filter").append(response.data.filter_options);
                }
                $(paginationSelector_shortcode ?? pagination_selector).html(response.data.pagination);
                syncCheckboxSelections();
                selectedFilterShowProductTop();
            } else {
                console.error('Error:', response.message);
                
            }
        }).fail(handleAjaxError);
    }
    function attachPaginationEvents() {
        $(document).on('click', ` ${paginationSelector_shortcode ?? pagination_selector} a.page-numbers`, function(e) {
            e.preventDefault(); // Prevent the default anchor click behavior
            const url = $(this).attr('href'); // Get the URL from the link
            const page = new URL(url).searchParams.get('paged'); // Extract the page number
            $('#roverlay').show();
            $('#loader').show();
            fetchFilteredProducts(page); // Fetch products for the selected page
        });
    }
    
    // Call this function after updating the product listings
    if($('#product-filter').length){
    attachPaginationEvents();
    }
    function changePseudoElementContent(beforeContent, afterContent) {
        // Create a new style element
        var style = $('<style></style>');
        style.text(`
            .progress-percentage:before { 
                content: "${beforeContent}"; 
            }
            .progress-percentage:after { 
                content: "${afterContent}"; 
            }
        `);
        
        // Append the style to the head
        $('head').append(style);
    }
    function gatherFormData() {
        const currentPageSlug = shortcodeCurrentPage || (path === "/" ? path : path.replace(/^\/|\/$/g, ''));
        const formData = $('#product-filter').serialize();
        
        // price range
        const rangeInput = document.querySelectorAll(".range-input input"),
        priceInput = document.querySelectorAll(".price-input input"),
        range = document.querySelector(".slider .progress");
        let minPrice = rangeInput[0]?parseInt(rangeInput[0].value):0,
        maxPrice = rangeInput[1]?parseInt(rangeInput[1].value):0;
        changePseudoElementContent(`$${minPrice}`, `$${maxPrice}`);
        rangeInput.forEach((input) => {
        input.addEventListener("input", (e) => {
          let minPrice = parseInt(rangeInput[0].value);
            maxPrice = parseInt(rangeInput[1].value);
            changePseudoElementContent(`$${minPrice}`, `$${maxPrice}`);
            priceInput[0].value = minPrice;
            priceInput[1].value = maxPrice;
            range.style.left = (minPrice / rangeInput[0].max) * 100 + "%";
            range.style.right = 100 - (maxPrice / rangeInput[1].max) * 100 + "%";
        });
      });
      priceInput.forEach((input) => {
        input.addEventListener("input", (e) => {
          let minPrice = parseInt(priceInput[0].value),
            maxPrice = parseInt(priceInput[1].value);
            if (e.target.className === "input-min") {
              rangeInput[0].value = minPrice;
              range.style.left = (minPrice / rangeInput[0].max) * 100 + "%";
            } else {
              rangeInput[1].value = maxPrice;
              range.style.right = 100 - (maxPrice / rangeInput[1].max) * 100 + "%";
            }
          
        });
      });
    //   price range ends
        // Append price filters if values exist
        let priceParams = '';
        if (minPrice) priceParams += `&min_price=${encodeURIComponent(minPrice)}`;
        if (maxPrice) priceParams += `&max_price=${encodeURIComponent(maxPrice)}`;
        
        return formData + priceParams + `&current-page=${encodeURIComponent(currentPageSlug)}`;
    }

    function handleAjaxError(xhr, status, error) {
        $('#roverlay').hide();
        $('#loader').hide();
        console.error('AJAX Error:', status, error);
    }
    function syncCheckboxSelections() {
        const $list = $('.rfilterbuttons ul').empty();
        if (!rfilterbuttonsId) {
            return;
        }
        const currentOptions = getCurrentSingleFilterOptions();
        const options = currentOptions.length ? currentOptions : getSingleFilterShortcodeOptions();

        for (const option of options) {
            $list.append(createCheckboxListItem(option.slug, isFilterValueChecked(option.slug), 'checkbox', option.name));
        }
        attachCheckboxClickEvents();
        attachMainFilterChangeEvents();
        $('.rfilterbuttons ul').off('scroll.dapfforwcNav').on('scroll.dapfforwcNav', updateSingleFilterNav);
        scrollCheckedSingleFilterIntoView();
        updateSingleFilterNav();
    }
    function getCurrentSingleFilterOptions() {
        const options = [];

        $('#product-filter #' + rfilterbuttonsId + ' input').each(function() {
            const $input = $(this);
            const label = $input.closest('label').find('.dapfforwc-option-text').first().text().trim() || $input.closest('label').text().trim();

            options.push({
                slug: $input.val(),
                name: label || formatFilterLabel($input.val())
            });
        });
        $('#product-filter #' + rfilterbuttonsId + ' option').each(function(index) {
            if (index === 0) {
                return;
            }

            options.push({
                slug: $(this).val(),
                name: $(this).text().trim() || formatFilterLabel($(this).val())
            });
        });

        return options.filter(function(option) {
            return option.slug;
        });
    }
    function getSingleFilterShortcodeOptions() {
        const rawOptions = $('.rfilterbuttons').first().attr('data-filter-options') || '[]';

        try {
            const parsedOptions = JSON.parse(rawOptions);

            if (!Array.isArray(parsedOptions)) {
                return [];
            }

            return parsedOptions.filter(function(option) {
                return option && option.slug;
            });
        } catch (error) {
            return [];
        }
    }
    function isFilterValueChecked(value) {
        const escapedValue = escapeSelectorValue(value);

        if ($(`#product-filter input[value="${escapedValue}"]:checked, #product-filter option[value="${escapedValue}"]:selected`).length) {
            return true;
        }

        return selectedValesbyuser.indexOf(value) !== -1;
    }
    function createCheckboxListItem(value, checked, type, label) {
        const formattedLabel = label || formatFilterLabel(value);
        return $('<li></li>').addClass(checked ? 'checked' : '').append(
            $('<input>', {
                name: 'attribute[' + rfilterbuttonsId + '][]',
                id: 'text_' + value,
                class: 'filter-checkbox',
                type: 'checkbox',
                value: value,
                checked: checked
            }).on('change', syncToMainFilter),
            $('<label></label>', {
                for: 'text_' + value,
                text: formattedLabel
            })
        );
    }

    function syncToMainFilter() {
        $(`#product-filter #${rfilterbuttonsId} input[value="${$(this).val()}"]`).prop('checked', $(this).is(':checked'));
        $(`#product-filter #${rfilterbuttonsId} select option[value="${$(this).val()}"]`).prop('selected', $(this).is(':checked'));
    }

    function attachCheckboxClickEvents() {
        $('.rfilterbuttons ul').off('click', 'li').on('click', 'li', function() {
            const checkbox = $(this).find('input');
            checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
            $(this).toggleClass('checked', checkbox.is(':checked'));
        });
    }

    function attachMainFilterChangeEvents() {
        $('#' + rfilterbuttonsId + ' input').off('change.dapfforwcSingle').on('change.dapfforwcSingle', function() {
            const relatedCheckbox = $(`.rfilterbuttons ul li input[value="${$(this).val()}"]`);
            relatedCheckbox.prop('checked', $(this).is(':checked')).closest('li').toggleClass('checked', $(this).is(':checked'));
            updateSingleFilterNav();
        });
    }

    function applyFiltersFromUrl(filtersString) {
        if (!filtersString) {
            return; // Early return if the string is empty
        }
    
        const filterValues = normalizeFilterValues(filtersString).filter(shouldShowFilterInUrl);
        filterValues.forEach(value => {
            // Check the input checkbox
            if ($(`input[value="${value}"]`).length) {
                $(`input[value="${value}"]`).attr('checked', true);
            } else if ($(`select option[value="${value}"]`).length) {
                // If no input found, check dropdown option
                $(`select option[value="${value}"]`).prop('selected', true);
            } else {
                console.log(`Filter "${value}" not found in inputs or dropdown.`);
            }
        });
    
        fetchFilteredProducts(); // Fetch products after applying filters
    }
    function updateUrlFilters() {
        const selectedFilters = new Set();
        $('#product-filter input:checked').each(function() {
            selectedFilters.add($(this).val());
        });
        // Gather selected options from the select dropdown
        $('#product-filter select').each(function() {
            // Add each selected option to the Set
            $(this).find('option:selected').each(function() {
                selectedFilters.add($(this).val());
            });
        });
        let filtersArray = Array.from(selectedFilters).filter(shouldShowFilterInUrl);
        const filterUse = (wcapf_options && wcapf_options.filters_word_in_permalinks) ? wcapf_options.filters_word_in_permalinks.replace(/^\/|\/$/g, '') : 'filters';
        const newUrl = buildFilterUrl(filterBaseUrl, filterUse, filtersArray);
        history.replaceState(null, '', newUrl);
    }
    function buildFilterUrl(baseUrl, filterUse, filtersArray) {
        let url;
        try {
            url = new URL(baseUrl, window.location.origin);
        } catch (error) {
            url = new URL(window.location.href);
        }
        url.hash = '';
        url.searchParams.delete('filters');

        if (filtersArray.length === 0) {
            return url.toString();
        }

        if (url.search) {
            url.searchParams.set('filters', filtersArray.join('/'));
            return url.toString();
        }

        url.pathname = url.pathname.replace(new RegExp(`/${filterUse}/.*$`), '/').replace(/\/?$/, '/');
        url.pathname = `${url.pathname}${filterUse}/${filtersArray.map(encodeURIComponent).join('/')}/`;
        return url.toString();
    }
    function getUrlFilterValues() {
        const params = new URLSearchParams(window.location.search);
        const queryFilters = params.get('filters') || '';
        const filters = queryFilters || getPathFilters();
        return normalizeFilterValues(filters).filter(shouldShowFilterInUrl);
    }
    function getPathFilters() {
        const filterUse = (wcapf_options && wcapf_options.filters_word_in_permalinks) ? wcapf_options.filters_word_in_permalinks.replace(/^\/|\/$/g, '') : 'filters';
        const marker = `/${filterUse}/`;
        const markerIndex = window.location.pathname.indexOf(marker);
        return markerIndex === -1 ? '' : window.location.pathname.slice(markerIndex + marker.length).replace(/^\/|\/$/g, '');
    }
    function normalizeFilterValues(values) {
        let rawValues = [];
        if (Array.isArray(values)) {
            rawValues = values;
        } else if (typeof values === 'string') {
            const trimmed = values.trim();
            if (trimmed.charAt(0) === '[') {
                try {
                    const parsedValues = JSON.parse(trimmed);
                    rawValues = Array.isArray(parsedValues) ? parsedValues : [];
                } catch (error) {
                    rawValues = trimmed.split(/[\/,]+/);
                }
            } else {
                rawValues = trimmed.split(/[\/,]+/);
            }
        }

        rawValues = rawValues.filter((value, index, allValues) => {
            return value !== 'page' && allValues[index - 1] !== 'page';
        });

        return Array.from(new Set(rawValues.map(value => String(value).trim()).filter(Boolean)));
    }
    function shouldShowFilterInUrl(value) {
        value = String(value || '').trim();
        return value !== '' && !urlExcludedFilterValues.has(value);
    }
    function escapeSelectorValue(value) {
        value = String(value || '');

        if ($.escapeSelector) {
            return $.escapeSelector(value);
        }

        if (window.CSS && window.CSS.escape) {
            return window.CSS.escape(value);
        }

        return value.replace(/(["\\])/g, '\\$1');
    }
    function formatFilterLabel(value) {
        return String(value || '').split('-').map(function(word) {
            return word.charAt(0).toUpperCase() + word.slice(1);
        }).join(' ');
    }
    function getFilterMeta(value) {
        const escapedValue = escapeSelectorValue(value);
        const $matches = $(`#product-filter input[value="${escapedValue}"], #product-filter option[value="${escapedValue}"]`);
        const groupPriority = ['popular-countries', 'conference-by-month', 'popular-cities', 'popular-topics', 'category', 'tag'];
        let $match = $matches.first();

        groupPriority.some(function(groupId) {
            const $groupMatch = $matches.filter(function() {
                return $(this).closest('.filter-group').attr('id') === groupId;
            }).first();

            if ($groupMatch.length) {
                $match = $groupMatch;
                return true;
            }

            return false;
        });

        const $group = $match.closest('.filter-group');
        const label = $match.is('option')
            ? $match.text().trim()
            : ($match.closest('label').find('.dapfforwc-option-text').first().text().trim() || $match.closest('label').text().trim());
        const iconHtml = $group.find('> .dapfforwc-filter-title .dapfforwc-filter-icon').first().html() || $group.find('.dapfforwc-filter-icon').first().html() || '';

        return {
            label: label || formatFilterLabel(value),
            iconHtml: iconHtml
        };
    }
    function createActiveFilterChip(value) {
        const meta = getFilterMeta(value);
        const $chip = $('<li></li>', {
            class: 'dapfforwc-active-filter-chip',
            'data-filter-value': value
        });

        if (meta.iconHtml) {
            $chip.append($('<span></span>', {
                class: 'dapfforwc-active-filter-icon',
                'aria-hidden': 'true'
            }).html(meta.iconHtml));
        }

        $chip.append($('<span></span>', {
            class: 'dapfforwc-active-filter-label',
            text: meta.label
        }));
        $chip.append($('<button></button>', {
            type: 'button',
            class: 'dapfforwc-active-filter-remove',
            'data-filter-value': value,
            'aria-label': 'Remove ' + meta.label
        }).html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>'));

        return $chip;
    }
    function setFilterValueChecked(value, checked) {
        const escapedValue = escapeSelectorValue(value);

        $(`#product-filter input[value="${escapedValue}"], .rfilterbuttons input[value="${escapedValue}"]`).prop('checked', checked);
        $(`#product-filter option[value="${escapedValue}"]`).prop('selected', checked);
        $(`.rfilterbuttons input[value="${escapedValue}"]`).closest('li').toggleClass('checked', checked);
    }
    function syncSingleFilterState() {
        $('.rfilterbuttons input.filter-checkbox').each(function() {
            $(this).closest('li').toggleClass('checked', $(this).is(':checked'));
        });
    }
    function updateSingleFilterNav() {
        $('.dapfforwc-single-filter-nav').each(function() {
            const $nav = $(this);
            const list = $nav.find('.rfilterbuttons ul').get(0);

            if (!list) {
                $nav.removeClass('has-overflow has-items has-single-item has-multiple-items is-at-start is-at-end');
                updateFilterToolbarState($nav.closest('.dapfforwc-filter-toolbar'));
                return;
            }

            const itemCount = $nav.find('.rfilterbuttons li').length;
            const maxScroll = Math.max(0, list.scrollWidth - list.clientWidth);
            const hasOverflow = itemCount > 1 && maxScroll > 2;
            const isAtStart = list.scrollLeft <= 2;
            const isAtEnd = list.scrollLeft >= maxScroll - 2;

            $nav.toggleClass('has-items', itemCount > 0);
            $nav.toggleClass('has-single-item', itemCount === 1);
            $nav.toggleClass('has-multiple-items', itemCount > 1);
            $nav.toggleClass('has-overflow', hasOverflow);
            $nav.toggleClass('is-at-start', !hasOverflow || isAtStart);
            $nav.toggleClass('is-at-end', !hasOverflow || isAtEnd);
            $nav.find('.dapfforwc-single-filter-arrow-prev').prop('disabled', !hasOverflow || isAtStart);
            $nav.find('.dapfforwc-single-filter-arrow-next').prop('disabled', !hasOverflow || isAtEnd);
            updateFilterToolbarState($nav.closest('.dapfforwc-filter-toolbar'));
        });
    }
    function updateActiveFilterNav() {
        $('.dapfforwc-active-filters').each(function() {
            const $nav = $(this);
            const list = $nav.find('.dapfforwc-active-filter-list').get(0);
            const itemCount = $nav.find('.dapfforwc-active-filter-chip').length;

            if (!list || !itemCount || $nav.is('[hidden]')) {
                if (!itemCount) {
                    $nav.attr('hidden', true);
                }
                $nav.removeClass('has-active-filters has-overflow is-at-start is-at-end');
                $nav.find('.dapfforwc-active-filter-arrow').prop('disabled', true);
                updateFilterToolbarState($nav.closest('.dapfforwc-filter-toolbar'));
                return;
            }

            const maxScroll = Math.max(0, list.scrollWidth - list.clientWidth);
            const hasOverflow = itemCount > 1 && maxScroll > 2;
            const isAtStart = list.scrollLeft <= 2;
            const isAtEnd = list.scrollLeft >= maxScroll - 2;

            $nav.addClass('has-active-filters');
            $nav.toggleClass('has-overflow', hasOverflow);
            $nav.toggleClass('is-at-start', !hasOverflow || isAtStart);
            $nav.toggleClass('is-at-end', !hasOverflow || isAtEnd);
            $nav.find('.dapfforwc-active-filter-arrow-prev').prop('disabled', !hasOverflow || isAtStart);
            $nav.find('.dapfforwc-active-filter-arrow-next').prop('disabled', !hasOverflow || isAtEnd);
            updateFilterToolbarState($nav.closest('.dapfforwc-filter-toolbar'));
        });
    }
    function updateFilterToolbarState($toolbars) {
        ($toolbars && $toolbars.length ? $toolbars : $('.dapfforwc-filter-toolbar')).each(function() {
            const $toolbar = $(this);
            const $active = $toolbar.children('.dapfforwc-active-filters');
            const $single = $toolbar.children('.dapfforwc-single-filter-nav');
            const hasActive = $active.length && !$active.is('[hidden]') && $active.find('.dapfforwc-active-filter-chip').length > 0;
            const singleItemCount = $single.find('.rfilterbuttons li').length;

            $toolbar.toggleClass('has-active-filters', !!hasActive);
            $toolbar.toggleClass('has-single-filter', $single.length > 0);
            $toolbar.toggleClass('has-single-filter-one-item', singleItemCount === 1);
            $toolbar.toggleClass('has-single-filter-overflow', $single.hasClass('has-overflow'));
        });
    }
    function scrollCheckedSingleFilterIntoView() {
        $('.rfilterbuttons ul').each(function() {
            const list = this;
            const $checked = $(list).find('li.checked').first();

            if (!$checked.length) {
                return;
            }

            const targetLeft = $checked.position().left + list.scrollLeft - ((list.clientWidth - $checked.outerWidth()) / 2);
            list.scrollLeft = Math.max(0, targetLeft);
        });
    }
    // create list of current selected filter
    function selectedFilterShowProductTop(){
        const activeValues = selectedValesbyuser.filter(shouldShowFilterInUrl);
        const $lists = $('.dapfforwc-active-filter-list, .rfilterselected ul').empty();
        const $containers = $('.dapfforwc-active-filters, .rfilterselected');

        if (!$lists.length) {
            return;
        }

        if (!activeValues.length) {
            $containers.attr('hidden', true).removeClass('has-active-filters has-overflow is-at-start is-at-end');
            updateActiveFilterNav();
            updateFilterToolbarState();
            return;
        }

        activeValues.forEach(function(value) {
            $lists.each(function() {
                $(this).append(createActiveFilterChip(value));
            });
        });
        $('.dapfforwc-active-filter-list').off('scroll.dapfforwcActiveNav').on('scroll.dapfforwcActiveNav', updateActiveFilterNav);
        $containers.removeAttr('hidden').addClass('has-active-filters');
        window.requestAnimationFrame(function() {
            updateActiveFilterNav();
            updateFilterToolbarState();
        });
    }
    selectedFilterShowProductTop();
    // for responsive
    function isMobile() {
        return $(window).width() <= 768;
    }
    function getMobileFilterGroups() {
        return $('#product-filter .dapfforwc-filter-card').filter(function() {
            const attributesWrapper = $(this).closest('#product-filter > .filter-group.attributes').get(0);

            if (this.style.display === 'none') {
                return false;
            }

            return !(attributesWrapper && attributesWrapper.style.display === 'none');
        });
    }
    function getMobileFilterTitle($group) {
        const $title = $group.find('> .dapfforwc-filter-title .dapfforwc-filter-title-text').first();
        const mobileTitle = $title.attr('data-mobile-title') || '';

        return mobileTitle.trim() || $title.text().trim();
    }
    function ensureMobileFilterTabs() {
        const $form = $('#product-filter');

        if (!$form.length) {
            return;
        }

        let $shell = $form.children('.dapfforwc-mobile-filter-shell');
        if (!$shell.length) {
            $shell = $('<div></div>', {
                class: 'dapfforwc-mobile-filter-shell',
                'aria-label': 'Product filters'
            }).append(
                $('<button></button>', {
                    type: 'button',
                    class: 'dapfforwc-mobile-filter-icon',
                    'aria-label': 'Filters'
                }).html('<svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 5h18"></path><path d="M6 12h12"></path><path d="M10 19h4"></path></svg>'),
                $('<div></div>', {
                    class: 'dapfforwc-mobile-filter-tabs',
                    role: 'tablist',
                    'aria-label': 'Filter groups'
                })
            );
            $form.prepend($shell);
        }

        const $groups = getMobileFilterGroups();
        const $tabs = $shell.find('.dapfforwc-mobile-filter-tabs').empty();

        if (!$groups.length) {
            $shell.attr('hidden', true);
            return;
        }

        $shell.removeAttr('hidden');

        if (isMobile()) {
            if (!$groups.filter('.is-mobile-open').length && !$form.hasClass('dapfforwc-mobile-tabs-ready')) {
                const storedGroup = $form.data('mobileOpenGroup');
                const $storedGroup = storedGroup ? $groups.filter('#' + escapeSelectorValue(storedGroup)).first() : $();
                ($storedGroup.length ? $storedGroup : $groups.first()).addClass('is-mobile-open');
            }
            $form.addClass('dapfforwc-mobile-tabs-ready');
        } else {
            $form.removeClass('dapfforwc-mobile-tabs-ready');
        }

        $groups.each(function() {
            const $group = $(this);
            const groupId = $group.attr('id') || '';
            const title = getMobileFilterTitle($group);
            const iconHtml = $group.find('> .dapfforwc-filter-title .dapfforwc-filter-icon').first().html() || '';
            const isActive = $group.hasClass('is-mobile-open');
            const $tab = $('<button></button>', {
                type: 'button',
                class: 'dapfforwc-mobile-filter-tab' + (isActive ? ' is-active' : ''),
                role: 'tab',
                'aria-selected': isActive ? 'true' : 'false',
                'aria-expanded': isActive ? 'true' : 'false',
                'data-filter-group': groupId
            });

            if (iconHtml) {
                $tab.append($('<span></span>', {
                    class: 'dapfforwc-mobile-filter-tab-icon',
                    'aria-hidden': 'true'
                }).html(iconHtml));
            }

            $tab.append($('<span></span>', {
                class: 'dapfforwc-mobile-filter-tab-text',
                text: title
            }));
            $tabs.append($tab);
        });
    }
    function toggleMobileFilterGroup($group) {
        const shouldOpen = !$group.hasClass('is-mobile-open');

        $('#product-filter .filter-group').not($group).removeClass('is-mobile-open')
            .find('> .dapfforwc-filter-title .dapfforwc-filter-toggle').attr('aria-expanded', 'false');
        $group.removeClass('is-collapsed').toggleClass('is-mobile-open', shouldOpen);
        $group.find('> .dapfforwc-filter-title .dapfforwc-filter-toggle').attr('aria-expanded', shouldOpen ? 'true' : 'false');
        $('#product-filter').data('mobileOpenGroup', shouldOpen ? ($group.attr('id') || '') : '');
        ensureMobileFilterTabs();
    }
    function closeMobileFilterGroups() {
        $('#product-filter .filter-group').removeClass('is-mobile-open')
            .find('> .dapfforwc-filter-title .dapfforwc-filter-toggle').attr('aria-expanded', 'false');
        $('#product-filter').data('mobileOpenGroup', '');
        ensureMobileFilterTabs();
    }
    function syncMobileFilterGroupState() {
        const $groups = $('#product-filter .filter-group');

        if (isMobile()) {
            $groups.filter('.is-mobile-open')
                .find('> .dapfforwc-filter-title .dapfforwc-filter-toggle').attr('aria-expanded', 'true');
            $groups.not('.is-mobile-open')
                .find('> .dapfforwc-filter-title .dapfforwc-filter-toggle').attr('aria-expanded', 'false');
            return;
        }

        $groups.removeClass('is-mobile-open')
            .find('> .dapfforwc-filter-title .dapfforwc-filter-toggle').attr('aria-expanded', 'true');
    }
    function textChange() {
        return;
    }
    textChange();
    syncMobileFilterGroupState();
     $(document).ajaxComplete(function() {
        textChange();
        ensureMobileFilterTabs();
        syncMobileFilterGroupState();
        noproductfound();
    });
            $(document).on('click', '.dapfforwc-mobile-filter-tab', function(event) {
                event.preventDefault();
                event.stopPropagation();

                const groupId = $(this).data('filter-group');
                const $group = groupId ? $('#product-filter #' + escapeSelectorValue(groupId)) : $();

                if ($group.length) {
                    toggleMobileFilterGroup($group);
                }
            });
            $(document).on('click', '.dapfforwc-mobile-filter-icon', function(event) {
                event.preventDefault();
                event.stopPropagation();

                const $openGroup = getMobileFilterGroups().filter('.is-mobile-open').first();
                if ($openGroup.length) {
                    closeMobileFilterGroups();
                    return;
                }

                const $firstGroup = getMobileFilterGroups().first();
                if ($firstGroup.length) {
                    toggleMobileFilterGroup($firstGroup);
                }
            });
            // Use event delegation for dynamically added elements
            $('#product-filter').on('click', '.title', function(event) {
                if (isMobile()) {
                    event.preventDefault();
                    event.stopPropagation();
                    toggleMobileFilterGroup($(this).closest('.filter-group'));
                }
            });
            $(document).on('click', function(event) {
                if (isMobile()) {
                    if ($(event.target).closest('#product-filter, .dapfforwc-filter-toolbar').length) {
                        return;
                    }

                    closeMobileFilterGroups();
                }
            });

            // Show message if no products found
                noproductfound();

    function noproductfound() {
        if ($("form#product-filter").children().length === 2) {
            $(productSelector_shortcode ?? product_selector).html('<p>No products found</p>');
            $(paginationSelector_shortcode ?? pagination_selector).html('');
        }
    }
});




// cateogry hide & show manage for herichical

jQuery(document).ready(function($) {
    $('.show-sub-cata').on('click', function(event) {
        event.preventDefault();
        const $childCategories = $(this).closest('a').next('.child-categories');
        $childCategories.slideToggle(() => {
            $(this).text($childCategories.is(':visible') ? '-' : '+');
        });
    });
});
