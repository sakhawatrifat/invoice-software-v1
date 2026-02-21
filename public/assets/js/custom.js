$('.ck-editor').closest('div').css('position', 'relative');

const ckeditors = new WeakMap();

function initializeCKEditors() {
    document.querySelectorAll('.ck-editor').forEach((element) => {
        // Skip elements not in the DOM
        if (!document.body.contains(element)) return;

        // If already initialized, destroy first
        if (ckeditors.has(element)) {
            const existingEditor = ckeditors.get(element);
            existingEditor.destroy()
                .then(() => {
                    ckeditors.delete(element);
                    createEditorInstance(element);
                })
                .catch(error => {
                    console.error('CKEditor destroy error:', error);
                });
        } else {
            createEditorInstance(element);
        }
    });
}

// function createEditorInstance(element) {
//     ClassicEditor
//         .create(element, {
//             toolbar: [
//                 'heading', '|',
//                 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|',
//                 'undo', 'redo'
//             ],
//             removePlugins: ['ImageUpload', 'EasyImage', 'MediaEmbed']
//         })
//         .then(editor => {
//             ckeditors.set(element, editor);
//             element.classList.add('ckeditor-initialized');
//         })
//         .catch(error => {
//             console.error('CKEditor initialization error:', error);
//         });
// }

function createEditorInstance(element) {
    ClassicEditor
        .create(element, {
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|',
                'undo', 'redo'
            ],
            removePlugins: ['ImageUpload', 'EasyImage', 'MediaEmbed'],
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    // Remove or skip heading 1
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                ]
            }
        })
        .then(editor => {
            ckeditors.set(element, editor);
            element.classList.add('ckeditor-initialized');
        })
        .catch(error => {
            console.error('CKEditor initialization error:', error);
        });
}
initializeCKEditors();


function initializeSummernote() {
    $('.summernote').each(function () {
        $(this).summernote({
            placeholder: $(this).attr('placeholder') || 'Type here...',
            tabsize: 2,
            height: 400,
            disableDragAndDrop: true,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                //['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });
}
initializeSummernote();



// Initialize flatpickr with conditional logic for datetime
$('.flatpickr-input').each(function () {
    const $this = $(this);
    const isDateTime = $this.hasClass('datetime');
    const is24Hour = $this.hasClass('24-hour');
    $this.flatpickr({
        enableTime: isDateTime,
        time_24hr: is24Hour,
        altInput: true,
        altFormat: isDateTime ? "Y-m-d H:i" : "Y-m-d",
        dateFormat: isDateTime ? "Y-m-d H:i" : "Y-m-d",
        onReady: function (selectedDates, dateStr, instance) {
            const clearButton = document.createElement('button');
            clearButton.type = 'button';
            clearButton.textContent = 'Clear';
            clearButton.className = 'flatpickr-clear-btn btn btn-sm btn-outline-secondary ms-2';
            clearButton.addEventListener('click', () => {
                instance.clear();
            });
            instance.calendarContainer.appendChild(clearButton);
        }
    });
});

function updateMinDateForDatetimeInputs() {
    try {
        // Select the main datetime element safely
        const $mainDatetime = $('.append-datepicker.main-datetime');
        if ($mainDatetime.length === 0) {
            return;
        }
        const mainDateVal = $mainDatetime.val()?.trim();
        if (!mainDateVal) {
            return;
        }
        // Parse date safely
        const mainDate = new Date(mainDateVal);
        if (isNaN(mainDate.getTime())) {
            return;
        }
        // Get the parent row safely
        const $row = $mainDatetime.closest('.append-item-container');
        if ($row.length === 0) {
            return;
        }
        // Find datetime inputs in the same row (excluding .main-datetime)
        const $datetimeInputs = $row.find('.append-datepicker.datetime:not(.main-datetime)');
        if ($datetimeInputs.length === 0) {
            return;
        }
        // Update minDate for each datetime input
        $datetimeInputs.each(function () {
            try {
                const instance = this._flatpickr;
                if (instance) {
                    instance.set('minDate', mainDate);
                    // Redraw calendar if open to reflect the change
                    if (instance.isOpen) {
                        instance.redraw();
                    }
                }
            } catch (innerErr) {
                // Handle error silently or log if needed
            }
        });
    } catch (error) {
        // Handle error silently or log if needed
    }
}

/**
 * Event handler for main datetime change
 */
$(document).on('change', '.append-datepicker.main-datetime', function () {
    updateMinDateForDatetimeInputs();
});

// Initialize safely on page load
updateMinDateForDatetimeInputs();


// Time-only picker
$('.flatpickr-input-time').flatpickr({
    enableTime: true,
    noCalendar: true,
    time_24hr: true, // remove if you want AM/PM
    altInput: true,
    altFormat: "H:i",
    dateFormat: "H:i",
    onReady: function (selectedDates, dateStr, instance) {
        const clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.textContent = 'Clear';
        clearButton.className = 'flatpickr-clear-btn btn btn-sm btn-outline-secondary ms-2';
        clearButton.addEventListener('click', () => {
            instance.clear();
        });
        instance.calendarContainer.appendChild(clearButton);
    }
});


// $('.wheel-datepicker-input').mobiscroll().datepicker({
//     controls: ['date', 'time'],
//     //display: 'center',
//     touchUi: true
// });

// document.querySelectorAll('.wheel-datepicker-input').forEach(input => {
//     // Check for 24-hour format class
//     const is24Hour = input.classList.contains('24-hours');

//     const picker = new Picker(input, {
//         controls: true,
//         format: is24Hour ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD HH:mm A', // 24h vs 12h format
//         headers: true,
//         center: true
//     });

//     input.addEventListener('click', () => {
//         picker.show();
//     });
// });


// MF Img popup
// handle events
$(document).on('click','.mf-prev',function(){
    $('body').addClass('mf-popup-visible');
    var img_src = $(this).attr('data-src');
    if(img_src == undefined){
        img_src = $(this).find('.mf-url').attr('data-src');
    }
    
    // Check if it's a PDF file
    var isPdf = img_src && (img_src.toLowerCase().endsWith('.pdf') || img_src.toLowerCase().includes('.pdf'));
    
    if(isPdf) {
        // Show PDF in iframe
        $('.mf-img-popup-img-wrap').hide();
        $('.mf-img-popup').find('img').hide();
        var iframe = $('.mf-img-popup').find('iframe');
        if(iframe.length === 0) {
            iframe = $('<iframe>').css({
                'width': '90%',
                'height': '90%',
                'border': 'none',
                'max-width': '90%',
                'max-height': '90%'
            });
            $('.mf-img-popup').append(iframe);
        }
        iframe.attr('src', img_src).show();
        $('.mf-img-popup-rotate-btn').hide();
    } else {
        // Show image
        $('.mf-img-popup-img-wrap').show();
        $('.mf-img-popup').find('iframe').hide();
        var $img = $('.mf-img-popup').find('img').attr('src', img_src).attr('data-rotation', '0').show();
        var el = $img[0];
        if (el && el.style) {
            el.style.removeProperty('transform');
            el.style.removeProperty('width');
            el.style.removeProperty('height');
        }
        $img.one('load', function() {
            mfPopupFitRotatedImage($img);
        });
        if (el && el.complete && el.naturalWidth) mfPopupFitRotatedImage($img);
        $('.mf-img-popup-rotate-btn').show();
    }
    
    $('.mf-img-popup').addClass('opened');
});

function mfPopupFitRotatedImage($img) {
    var el = $img[0];
    if (!el || !el.naturalWidth) return;
    var rot = parseInt($img.attr('data-rotation') || '0', 10) % 360;
    var nw = el.naturalWidth;
    var nh = el.naturalHeight;
    var vpW = window.innerWidth * 0.88;
    var vpH = window.innerHeight * 0.88;
    var scale, w, h;
    if (rot === 90 || rot === 270) {
        scale = Math.min(1, vpW / nh, vpH / nw);
        w = Math.floor(nh * scale);
        h = Math.floor(nw * scale);
    } else {
        scale = Math.min(1, vpW / nw, vpH / nh);
        w = Math.floor(nw * scale);
        h = Math.floor(nh * scale);
    }
    el.style.setProperty('width', w + 'px', 'important');
    el.style.setProperty('height', h + 'px', 'important');
    el.style.setProperty('transform', 'rotate(' + rot + 'deg)', 'important');
}

$(document).on('click','.mf-img-popup-rotate-btn', function(e){
    e.stopPropagation();
    var $img = $('.mf-img-popup').find('img');
    if (!$img.is(':visible')) return;
    var rot = parseInt($img.attr('data-rotation') || '0', 10);
    rot = (rot + 90) % 360;
    $img.attr('data-rotation', rot);
    mfPopupFitRotatedImage($img);
});

$(document).on('click','.mf-img-popup, .mf-img-popup-close-btn',function(){
    $('body').removeClass('mf-popup-visible');
    $('.mf-img-popup').removeClass('opened');
    var $img = $('.mf-img-popup').find('img');
    $img.attr('src', '').attr('data-rotation', '0');
    var el = $img[0];
    if (el && el.style) {
        el.style.removeProperty('transform');
        el.style.removeProperty('width');
        el.style.removeProperty('height');
    }
    $('.mf-img-popup').find('iframe').attr('src', '').hide();
});

$('.mf-img-popup img').on('click', function(e) {
    e.stopPropagation();
});

function formatOption(option) {
    if (!option.id) return option.text;

    const $select = $(option.element).closest('select');
    const imageUrl = $(option.element).data('image');
    const text = option.text;
    const extraClass = $select.data('class') || ''; // get data-class from <select>

    if (!imageUrl) return text;

    return $(`
        <span>
            <img src="${imageUrl}" class="img-thumbnail me-2 ${extraClass}">
            ${text}
        </span>
    `);
}


$(document).ready(function () {
    // Add data-control="select2" and data-placeholder to all form-select that don't have them
    $('select.form-select:not([data-control="select2"])').each(function() {
        var $select = $(this);
        if (!$select.attr('data-control')) {
            $select.attr('data-control', 'select2');
        }
        if (!$select.attr('data-placeholder')) {
            var defaultPlaceholder = typeof getCurrentTranslation !== 'undefined' && getCurrentTranslation.select_an_option 
                ? getCurrentTranslation.select_an_option 
                : 'select_an_option';
            $select.attr('data-placeholder', defaultPlaceholder);
        }
    });
    
    // Initialize all selects with data-control="select2"
    $('select[data-control="select2"]').each(function() {
        var $select = $(this);
        var placeholder = $select.data('placeholder') || "Select an option";
        if ($select.hasClass('select2-with-images')) {
            if (!$select.hasClass('select2-hidden-accessible')) {
                $select.select2({
                    templateResult: formatOption,
                    templateSelection: formatOption,
                    escapeMarkup: function(m) { return m; },
                    placeholder: placeholder,
                    width: '100%'
                });
            }
        } else if (!$select.hasClass('select2-hidden-accessible')) {
            $select.select2({
                placeholder: placeholder,
                width: '100%'
            });
        }
    });

    $('.select-2-tag').select2({
        tags: true, // allow new values
        tokenSeparators: [',', ' '], // type comma or space to add new email
        placeholder: "Type & Enter...",
        allowClear: true,
    });

    $('.select-2-mail').select2({
        tags: true,
        tokenSeparators: [',', ' '],
        placeholder: "Type & Enter...",
        allowClear: true,
        createTag: function (params) {
            var term = $.trim(params.term);

            if (term === '') {
                return null;
            }

            // Simple email regex
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailRegex.test(term)) {
                // ❌ If not valid email, don't create the tag
                return null;
            }

            // ✅ Valid email → create tag
            return {
                id: term,
                text: term,
                newTag: true
            };
        }
    });
});

$(document).ready(function () {
    $('select.dynamic-option').each(function () {
        var $select = $(this);
        var placeholder = $select.data('placeholder') || '--Select or type--';

        $select.select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            tags: true,               // enable adding new items
            tokenSeparators: [','],   // split new items by comma if typing multiple
            createTag: function (params) {
                var term = $.trim(params.term);
                if (term === '') return null;
                return {
                    id: term,       // use the typed term as id and text
                    text: term,
                    newTag: true    // mark it as new
                };
            }
        });
    });
});


$(document).on('click', '.toggle-password', function () {
    const $input = $(this).closest('div').find('.password');
    const isVisible = $(this).attr('data-visible') === 'true';

    $input.attr('type', isVisible ? 'password' : 'text');
    $(this).attr('data-visible', !isVisible);
});

$(document).on('click', '.text-toggle-password', function () {
    const $container = $(this).closest('.password-container');
    const $passwordSpan = $container.find('.copy-target');
    const actualPassword = $container.find('[data-value]').data('value');
    const isVisible = $(this).attr('data-visible') === 'true';

    if (isVisible) {
        // Hide password
        $passwordSpan.text('********');
        $(this).attr('data-visible', 'false');
    } else {
        // Show actual password
        $passwordSpan.text(actualPassword);
        $(this).attr('data-visible', 'true');
    }
});




// Integer number
$(document).on('input', '.integer-validate', function (e) {
    var inputValue = $(this).val();
    var maxLimit = parseFloat($(this).attr('max'));
    var maxLength = 10;
    var regex = /^(\d*)$/;

    if (regex.test(inputValue) && inputValue.length <= maxLength) {
        var numericValue = parseFloat(inputValue);

        // Check if the numeric value is within the max limit
        if (isNaN(maxLimit) || numericValue <= maxLimit || isNaN(numericValue)) {
            $(this).data('prev-value', inputValue);
        } else {
            // Revert to the last valid value
            $(this).val($(this).data('prev-value') || '');
        }
    } else {
        // Revert to the last valid value
        $(this).val($(this).data('prev-value') || '');
    }
});
// Save the initial value for backspacing
$(document).on('focus', '.integer-validate', function () {
    $(this).data('prev-value', $(this).val());
});

// Float number
$(document).on('input', '.number-validate', function (e) {
    var inputValue = $(this).val();
    var maxLimit = parseFloat($(this).attr('max'));
    var maxLength = 10;
    var regex = /^\d*\.?\d*$/;

    if (regex.test(inputValue) && inputValue.length <= maxLength) {
        var numericValue = parseFloat(inputValue);

        // Check if the numeric value is within the max limit
        if (isNaN(maxLimit) || numericValue <= maxLimit || isNaN(numericValue)) {
            $(this).data('prev-value', inputValue);
        } else {
            // Revert to the last valid value
            $(this).val($(this).data('prev-value') || '');
        }
    } else {
        // Revert to the last valid value
        $(this).val($(this).data('prev-value') || '');
    }
});
// Save the initial value for backspacing
$(document).on('focus', '.number-validate', function () {
    $(this).data('prev-value', $(this).val());
});



function extractPrimaryCity(text = null) {
    if (!text || text.trim() === '') {
        return 'N/A';
    }

    // Step 1: Remove anything in parentheses (e.g., (NRT), (DAC))
    text = text.replace(/\s*\([^)]*\)/g, '');

    // Step 2: Remove common non-city keywords
    const patterns = [
        /\b(International|Airport|Apt|Terminal|Station|Railway|Bus|Port|Intl|Airfield|Air\s*Base|City|Departure|Arrival|Domestic|Runway|Flight|Airlines|Transfer|Connection)\b/gi
    ];
    patterns.forEach(pattern => {
        text = text.replace(pattern, '');
    });

    // Step 3: Normalize spacing
    text = text.replace(/\s+/g, ' ').trim();

    const words = text.split(' ').filter(Boolean);
    const input = text.toLowerCase();

    // Step 4: Multi-word city list (lowercased)
    const multiWordCities = [
        // Asia
        'kuala lumpur', 'ho chi minh', 'hong kong', 'abu dhabi', 'new delhi', 'sri jayawardenepura kotte', 'tel aviv',
        'phnom penh', 'davao city', 'islamabad capital territory', 'bandar seri begawan',

        // Europe
        'san marino', 'vatican city', 'the hague', 'belfast city', 'las palmas', 'sankt petersburg', 'united kingdom',

        // North America
        'new york', 'los angeles', 'san francisco', 'san diego', 'san jose', 'mexico city', 'guatemala city', 'panama city',
        'kansas city', 'salt lake city', 'oklahoma city', 'las vegas', 'fort lauderdale', 'san antonio',

        // South America
        'buenos aires', 'rio de janeiro', 'são paulo', 'santa cruz', 'la paz', 'san juan', 'porto alegre', 'santiago del estero',

        // Africa
        'cape town', 'port elizabeth', 'east london', 'dar es salaam', 'abidjan city', 'addis ababa', 'ouagadougou city',

        // Oceania
        'port moresby', 'newcastle city', 'gold coast', 'wellington city',

        // Middle East
        'riyadh city', 'mecca city', 'al ain', 'ras al khaimah', 'medina city',

        // Caribbean & Island nations
        'san juan', 'port au prince', 'saint george’s', 'kingston city'
    ];

    // Step 5: Try to match the longest multi-word city name from the start
    for (let i = 4; i >= 1; i--) {
        const chunk = words.slice(0, i).join(' ').toLowerCase();
        if (multiWordCities.includes(chunk)) {
            return chunk.split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }
    }

    // Fallback: return first word as city
    return words.length ? (words[0].charAt(0).toUpperCase() + words[0].slice(1)) : '';
}
