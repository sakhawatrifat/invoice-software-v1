@php
	$getCurrentTranslation = getCurrentTranslation();
	$upcomingCount = isset($upcomingStickyNotes) ? $upcomingStickyNotes->count() : 0;
@endphp
<div
    id="kt_sticky_note"
    class="bg-body"
    data-upcoming-url="{{ route('sticky_note.upcomingDrawerData') }}"
    data-kt-drawer="true"
    data-kt-drawer-name="sticky_note"
    data-kt-drawer-activate="true"
    data-kt-drawer-overlay="true"
    data-kt-drawer-width="{default:'300px', 'lg': '900px'}"
    data-kt-drawer-direction="end"
    data-kt-drawer-toggle="#kt_sticky_note_toggle"
    data-kt-drawer-close="#kt_sticky_note_close"
>
    <div class="card shadow-none border-0 rounded-0">
        <!--begin::Header-->
        <div class="card-header" id="kt_sticky_note_header">
            <h3 class="card-title fw-bold text-gray-900">{{ $getCurrentTranslation['upcoming_sticky_notes'] ?? 'upcoming_sticky_notes' }}</h3>

            <div class="card-toolbar">
                <button
                    type="button"
                    class="btn btn-sm btn-icon btn-active-light-primary me-n5"
                    id="kt_sticky_note_close"
                >
                    <i class="fas fa-times fa-2x"></i>
                </button>
            </div>
        </div>
        <!--end::Header-->

        <!--begin::Body-->
        <div class="card-body position-relative" id="kt_sticky_note_body">
            <!--begin::Content-->
            <div
                id="kt_sticky_note_scroll"
                class="position-relative scroll-y me-n5 pe-5"
                data-kt-scroll="true"
                data-kt-scroll-height="auto"
                data-kt-scroll-wrappers="#kt_sticky_note_body"
                data-kt-scroll-dependencies="#kt_sticky_note_header, #kt_sticky_note_footer"
                data-kt-scroll-offset="5px"
            >
                <!--begin::Timeline items-->
                <div class="timeline timeline-border-dashed">

                    <!--begin::Timeline item-->
                    <div class="timeline-item">
                        <div class="timeline-line"></div>
                        <div class="timeline-icon">
                            <i class="ki-duotone ki-message-text-2 fs-2 text-gray-500">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </div>

                        <div class="timeline-content mb-10 mt-n1">
                            <div class="pe-3 mb-5">
                                <div class="fs-5 fw-semibold mb-1 w-100 mt-1">
                                    {{ $getCurrentTranslation['upcoming_sticky_notes'] ?? 'upcoming_sticky_notes' }} (7 {{ $getCurrentTranslation['days'] ?? 'days' }})
                                </div>
                                <div class="fs-7 text-muted" id="kt_sticky_note_count_text">
                                    {{ str_replace(':count', (string) $upcomingCount, $getCurrentTranslation['you_have_count_notes'] ?? 'You have :count notes.') }}
                                </div>
                            </div>

                            <div class="overflow-auto pb-5" id="kt_sticky_note_list">
                                @include('common._partials.sticky-notes-drawer-list')
                            </div>
                        </div>
                    </div>
                    <!--end::Timeline item-->
                </div>
                <!--end::Timeline items-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Body-->

        <!--begin::Footer-->
        <div class="card-footer py-5 text-center" id="kt_sticky_note_footer">
            <a href="{{ route('sticky_note.index') }}" class="btn btn-bg-body text-primary">
                {{ $getCurrentTranslation['see_more'] ?? 'see_more' }}
                <i class="ki-duotone ki-arrow-right fs-3 text-primary">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </a>
        </div>
        <!--end::Footer-->
    </div>
</div>
