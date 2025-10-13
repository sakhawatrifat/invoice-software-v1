<style>
	@keyframes blink-bg {
		0%   { background-color: #a5a7a5; }
		50%  { background-color: transparent; }
		100% { background-color: #a5a7a5; }
	}

	.blink-highlight {
		animation: blink-bg 0.5s ease-in-out 2;
	}
</style>

<script>

	$(document).ready(function () {
		updateAppendItemCountText();
		resetAppendToolbar();

		// Append parent item
		$(document).on('click', '.append-item-add-btn', function () {
			let appendContainer = $(this).closest('.append-item-container');
			let wrapper = appendContainer.find('.append-item-wrapper');
			let firstItem = wrapper.find('.append-item').first();

			// Clone the DOM structure only (not events or data)
			let newItem = firstItem.clone(false, false);

			// CLEAN any existing plugin artifacts from clone
			if(newItem.find('.flatpickr-input').length > 0){
				newItem.find('.flatpickr-input').each(function () {
					$(this).closest('div').find('input.input').remove();

					if (this._flatpickr) {
						this._flatpickr.destroy();
					}
					$(this).removeClass('flatpickr-input').removeAttr('readonly');
				});
			}
			newItem.find('.select2-container').remove();
			newItem.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').removeAttr('tabindex').removeAttr('aria-hidden');

			// Clear input/select/textarea values
			newItem.find('input, select, textarea').val('');

			// Handle nested child item
			newItem.find('.append-child-item-wrapper').each(function () {
				let childWrapper = $(this);
				let firstChild = childWrapper.find('.append-child-item').first().clone(false, false);

				// Clean plugins
				if(firstChild.find('.flatpickr-input').length > 0){
					firstChild.find('.flatpickr-input').each(function () {
						$(this).closest('div').find('input.input').remove();

						if (this._flatpickr) {
							this._flatpickr.destroy();
						}
						$(this).removeClass('flatpickr-input').removeAttr('readonly');
					});
				}
				firstChild.find('.select2-container').remove();
				firstChild.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').removeAttr('tabindex').removeAttr('aria-hidden');

				firstChild.find('input, select, textarea').val('');
				childWrapper.html(firstChild);
			});

			// Append and show the item
			newItem.hide();
			wrapper.append(newItem);
			//newItem.slideDown();
			newItem.slideDown({
				complete: function () {
					newItem.addClass('blink-highlight');

					// Remove the class after animation ends (1.5s = 0.5s * 3 blinks)
					setTimeout(function () {
						newItem.removeClass('blink-highlight');
					}, 1500);

					// Reinitialize plugins ONLY on the new item
					setTimeout(function () {
						reinitPlugins(newItem);
					}, 100);
				}
			});

			// Reinitialize plugins ONLY on the new item
			setTimeout(function () {
				reinitPlugins(newItem);
			}, 100);

			updateAppendItemCountText();
			resetAppendToolbar();

			$('html, body').animate({
				scrollTop: newItem.offset().top - 100
			}, 500);
		});


		// Remove parent item
		$(document).on('click', '.append-item-remove-btn', function () {
			let appendItem = $(this).closest('.append-item');

			Swal.fire({
				title: 'Are you sure?',
				text: "This item will be removed & you might not be able to revert it.",
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'Yes, delete it!',
				reverseButtons: true
			}).then((result) => {
				if (result.isConfirmed) {
					appendItem.slideUp(300, function () {
						$(this).remove();
						updateAppendItemCountText();
						resetAppendToolbar();
					});
				}
			});
		});

		// Append child item
		$(document).on('click', '.append-child-item-add-btn', function () {
			let wrapper = $(this).closest('.append-child-item-wrapper');
			let firstChild = wrapper.find('.append-child-item').first();
			let newChild = firstChild.clone(false, false);

			// Clean plugins
			if(newChild.find('.flatpickr-input').length > 0){
				newChild.find('.flatpickr-input').each(function () {
					$(this).closest('div').find('input.input').remove();

					if (this._flatpickr) {
						this._flatpickr.destroy();
					}
					$(this).removeClass('flatpickr-input').removeAttr('readonly');
				});
			}
			newChild.find('.select2-container').remove();
			newChild.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').removeAttr('tabindex').removeAttr('aria-hidden');

			// Clear inputs
			newChild.find('input, select, textarea').val('');

			newChild.hide();
			wrapper.append(newChild);
			//newChild.slideDown();
			newChild.slideDown({
				complete: function () {
					newChild.addClass('blink-highlight');

					// Remove the class after animation ends (1.5s = 0.5s * 3 blinks)
					setTimeout(function () {
						newChild.removeClass('blink-highlight');
					}, 1500);

					// Reinitialize plugins ONLY on the new item
					setTimeout(function () {
						reinitPlugins(newChild);
					}, 100);
				}
			});

			// Reinitialize plugins ONLY on the new item
			setTimeout(function () {
				reinitPlugins(newChild);
			}, 100);

			resetAppendToolbar();
			updateAppendItemCountText(); // reindex child names

			$('html, body').animate({
				scrollTop: newChild.offset().top - 100
			}, 500);
		});

		// Remove child item
		$(document).on('click', '.append-child-item-remove-btn', function () {
			let childItem = $(this).closest('.append-child-item');

			Swal.fire({
				title: 'Are you sure?',
				text: "This profile will be removed.",
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'Yes, delete it!',
				reverseButtons: true
			}).then((result) => {
				if (result.isConfirmed) {
					childItem.slideUp(300, function () {
						$(this).remove();

						resetAppendToolbar();
						updateAppendItemCountText(); // reindex after removal
					});
				}
			});
		});
	});


	function resetAppendToolbar() {
		$('.append-item-container').each(function () {
			let items = $(this).find('.append-item-wrapper .append-item');
			items.find('.append-item-remove-btn').hide();

			if (items.length > 1) {
				items.not(':first').find('.append-item-remove-btn').show();
			}

			items.each(function () {
				let childItems = $(this).find('.append-child-item-wrapper .append-child-item');
				childItems.find('.append-child-item-add-btn').show();
				childItems.find('.append-child-item-remove-btn').hide();

				if (childItems.length > 1) {
					childItems.not(':first').find('.append-child-item-add-btn').hide();
					childItems.not(':first').find('.append-child-item-remove-btn').show();
				}
			});
		});
	}

	function reinitPlugins(container) {
		container.find('.append-datepicker').flatpickr({
			altInput: true,
			altFormat: "Y-m-d",
			dateFormat: "Y-m-d",
		});

		container.find('select[data-control="select2"]').select2({
			placeholder: "Select an option",
			width: '100%'
		});
	}

	function updateAppendItemCountText() {
		$('.append-item-container').each(function () {
			const appendItems = $(this).find('.append-item-wrapper .append-item');

			appendItems.each(function (parentIndex) {
				const parentItem = $(this);
				parentItem.find('.append-item-count').text(parentIndex + 1);

				const childWrapper = parentItem.find('.append-child-item-wrapper');
				if (childWrapper.length > 0) {
					const childItems = childWrapper.find('.append-child-item');

					childItems.each(function (childIndex) {
						$(this).find('.append-child-item-count').text(childIndex + 1);
					});
				}
			});
		});
	}

	function resetAppendIndexes() {
		const parentIndexes = [];
		const childIndexes = [];

		$('.append-item-container').each(function () {
			const appendItems = $(this).find('.append-item-wrapper .append-item');

			appendItems.each(function (parentIndex) {
				const parentItem = $(this);
				parentIndexes.push(parentIndex);

				// Update names for parent
				parentItem.find('[name]').each(function () {
					let name = $(this).attr('name');
					if (name) {
						let updated = name.replace(/\[parent_key\]/g, `[${parentIndex}]`);
						$(this).attr('name', updated);
					}
				});

				// Update child names
				const childWrapper = parentItem.find('.append-child-item-wrapper');
				if (childWrapper.length > 0) {
					const childItems = childWrapper.find('.append-child-item');

					childItems.each(function (childIndex) {
						childIndexes.push({
							parentIndex,
							childIndex
						});

						$(this).find('[name]').each(function () {
							let name = $(this).attr('name');
							if (name) {
								let updated = name
									.replace(/\[parent_key\]/g, `[${parentIndex}]`)
									.replace(/\[child_key\]/g, `[${childIndex}]`);
								$(this).attr('name', updated);
							}
						});
					});
				}
			});
		});
	}




</script>