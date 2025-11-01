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
    });
});

$('.flatpickr-input-time').flatpickr({
    enableTime: true,
    noCalendar: true,
    time_24hr: true, // remove if you want AM/PM
    altInput: true,
    altFormat: "H:i",
    dateFormat: "H:i",
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
    $('.mf-img-popup').children('img').attr('src', img_src);
    $('.mf-img-popup').addClass('opened');
});

$(document).on('click','.mf-img-popup, .mf-img-popup-close-btn',function(){
    $('body').removeClass('mf-popup-visible');
    $('.mf-img-popup').removeClass('opened');
    $('.mf-img-popup').children('img').attr('src', '');
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
    $('.select2-with-images').select2({
        templateResult: formatOption,
        templateSelection: formatOption,
        escapeMarkup: function(m) { return m; } // allows HTML rendering
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
