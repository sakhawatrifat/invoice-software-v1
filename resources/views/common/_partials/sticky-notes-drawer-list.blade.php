@php
	$getCurrentTranslation = getCurrentTranslation();
@endphp
@if(isset($upcomingStickyNotes) && $upcomingStickyNotes->isNotEmpty())
	@foreach($upcomingStickyNotes as $sn)
	@php
		$now = \Carbon\Carbon::now();
		$reminderPassed = $sn->reminder_datetime && $sn->reminder_datetime->isPast();
		$deadlinePassed = $sn->deadline && $sn->deadline->isPast();
		$readStatus = (bool) ($sn->read_status ?? false);
	@endphp
	<a
		href="{{ route('sticky_note.show', $sn->id) }}"
		class="text-reset text-decoration-none"
	>
		<div
			class="d-flex align-items-center border rounded min-w-750px px-7 py-3 mb-3 position-relative
			       transition-all duration-200
			       {{ $readStatus ? 'bg-light border-gray-300' : 'bg-primary border-primary shadow-sm' }}
			       hover-shadow-sm hover-border-primary"
			style="cursor: pointer;"
		>
			<span class="fs-5 fw-semibold {{ $readStatus ? 'text-gray-700' : 'text-white' }}">
				{{ $sn->note_title }}
			</span>
			<span class="badge ms-2 {{ $readStatus ? 'badge-light-primary' : 'badge-light' }}">{{ $sn->status }}</span>
			@if($sn->reminder_datetime || $sn->deadline)
				<span class="ms-auto fs-7 {{ $readStatus ? 'text-muted' : 'text-white' }}">
					@if($sn->reminder_datetime)
						<span class="{{ $reminderPassed ? 'text-danger' : '' }}">
							{{ $getCurrentTranslation['reminder'] ?? 'reminder' }}: {{ $sn->reminder_datetime->format('d M Y, H:i') }}
						</span>
					@endif
					@if($sn->reminder_datetime && $sn->deadline) <span>·</span> @endif
					@if($sn->deadline)
						<span class="{{ $deadlinePassed ? 'text-danger' : '' }}">
							{{ $getCurrentTranslation['deadline'] ?? 'deadline' }}: {{ $sn->deadline->format('d M Y') }}
						</span>
					@endif
				</span>
			@endif
		</div>
	</a>
	@endforeach
@else
	<div class="text-center text-muted py-5">
		{{ $getCurrentTranslation['no_upcoming_sticky_notes'] ?? 'no_upcoming_sticky_notes' }}
	</div>
@endif
