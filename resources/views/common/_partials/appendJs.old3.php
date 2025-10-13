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
		resetAppendIndexes();

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

				// ✅ Handle inner-child-item inside this child
				firstChild.find('.append-inner-child-item-wrapper').each(function () {
					let innerWrapper = $(this);
					let firstInner = innerWrapper.find('.append-inner-child-item').first().clone(false, false);

					// Clean plugins
					if(firstInner.find('.flatpickr-input').length > 0){
						firstInner.find('.flatpickr-input').each(function () {
							$(this).closest('div').find('input.input').remove();

							if (this._flatpickr) {
								this._flatpickr.destroy();
							}
							$(this).removeClass('flatpickr-input').removeAttr('readonly');
						});
					}
					firstInner.find('.select2-container').remove();
					firstInner.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').removeAttr('tabindex').removeAttr('aria-hidden');

					firstInner.find('input, select, textarea').val('');

					// Reset the inner wrapper with only one cleaned clone
					innerWrapper.html(firstInner);
				});

				// Set this single cleaned child as the wrapper content
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

			resetAppendIndexes();

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
						resetAppendIndexes();
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
			if (newChild.find('.flatpickr-input').length > 0) {
				newChild.find('.flatpickr-input').each(function () {
					$(this).closest('div').find('input.input').remove();

					if (this._flatpickr) {
						this._flatpickr.destroy();
					}
					$(this).removeClass('flatpickr-input').removeAttr('readonly');
				});
			}
			newChild.find('.select2-container').remove();
			newChild.find('select').removeClass('select2-hidden-accessible')
				.removeAttr('data-select2-id')
				.removeAttr('tabindex')
				.removeAttr('aria-hidden');

			// Clear inputs
			newChild.find('input, select, textarea').val('');

			// ✅ Handle inner-child cloning
			newChild.find('.append-inner-child-item-wrapper').each(function () {
				let innerWrapper = $(this);
				let firstInner = innerWrapper.find('.append-inner-child-item').first().clone(false, false);

				// Clean inner plugins
				if (firstInner.find('.flatpickr-input').length > 0) {
					firstInner.find('.flatpickr-input').each(function () {
						$(this).closest('div').find('input.input').remove();

						if (this._flatpickr) {
							this._flatpickr.destroy();
						}
						$(this).removeClass('flatpickr-input').removeAttr('readonly');
					});
				}
				firstInner.find('.select2-container').remove();
				firstInner.find('select').removeClass('select2-hidden-accessible')
					.removeAttr('data-select2-id')
					.removeAttr('tabindex')
					.removeAttr('aria-hidden');

				firstInner.find('input, select, textarea').val('');

				// Reset with only one clean inner-child
				innerWrapper.html(firstInner);
			});

			newChild.hide();
			wrapper.append(newChild);
			newChild.slideDown({
				complete: function () {
					newChild.addClass('blink-highlight');

					setTimeout(function () {
						newChild.removeClass('blink-highlight');
					}, 1500);

					setTimeout(function () {
						reinitPlugins(newChild);
					}, 100);
				}
			});

			setTimeout(function () {
				reinitPlugins(newChild);
			}, 100);

			resetAppendToolbar();
			updateAppendItemCountText(); // reindex child names
			resetAppendIndexes();

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
						resetAppendIndexes();
					});
				}
			});
		});


		$(document).on('click', '.append-inner-child-item-add-btn', function () {
			let wrapper = $(this).closest('.append-inner-child-item-wrapper');
			let firstInner = wrapper.find('.append-inner-child-item').first();
			let newInner = firstInner.clone(false, false);

			// Clean plugins
			if (newInner.find('.flatpickr-input').length > 0) {
				newInner.find('.flatpickr-input').each(function () {
					$(this).closest('div').find('input.input').remove();

					if (this._flatpickr) {
						this._flatpickr.destroy();
					}
					$(this).removeClass('flatpickr-input').removeAttr('readonly');
				});
			}
			newInner.find('.select2-container').remove();
			newInner.find('select').removeClass('select2-hidden-accessible')
				.removeAttr('data-select2-id')
				.removeAttr('tabindex')
				.removeAttr('aria-hidden');

			// Clear inputs
			newInner.find('input, select, textarea').val('');

			newInner.hide();
			wrapper.append(newInner);
			newInner.slideDown({
				complete: function () {
					newInner.addClass('blink-highlight');

					setTimeout(function () {
						newInner.removeClass('blink-highlight');
					}, 1500);

					setTimeout(function () {
						reinitPlugins(newInner);
					}, 100);
				}
			});

			setTimeout(function () {
				reinitPlugins(newInner);
			}, 100);

			resetAppendToolbar();
			updateAppendItemCountText(); // reindex inner-child names
			resetAppendIndexes();

			$('html, body').animate({
				scrollTop: newInner.offset().top - 100
			}, 500);
		});


		$(document).on('click', '.append-inner-child-item-remove-btn', function () {
			let innerChildItem = $(this).closest('.append-inner-child-item');

			Swal.fire({
				title: 'Are you sure?',
				text: "This item will be removed.",
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'Yes, delete it!',
				reverseButtons: true
			}).then((result) => {
				if (result.isConfirmed) {
					innerChildItem.slideUp(300, function () {
						$(this).remove();

						resetAppendToolbar();
						updateAppendItemCountText(); // update counts or labels
						resetAppendIndexes(); // reindex input names properly
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

				// Handle .append-inner-child-item inside each child item
				childItems.each(function () {
					let innerItems = $(this).find('.append-inner-child-item-wrapper .append-inner-child-item');
					innerItems.find('.append-inner-child-item-add-btn').show();
					innerItems.find('.append-inner-child-item-remove-btn').hide();

					if (innerItems.length > 1) {
						innerItems.not(':first').find('.append-inner-child-item-add-btn').hide();
						innerItems.not(':first').find('.append-inner-child-item-remove-btn').show();
					}
				});
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
						const childItem = $(this);
						childItem.find('.append-child-item-count').text(childIndex + 1);

						const innerWrapper = childItem.find('.append-inner-child-item-wrapper');
						if (innerWrapper.length > 0) {
							const innerItems = innerWrapper.find('.append-inner-child-item');

							innerItems.each(function (innerIndex) {
								$(this).find('.append-inner-child-item-count').text(innerIndex + 1);
							});
						}
					});
				}
			});
		});
	}


	function resetAppendIndexes() {
	$('.append-item-container').each(function () {
		const container = $(this);
		const parentWrapper = container.find('.append-item-wrapper').first();

		// Level 1: .append-item
		parentWrapper.children('.append-item').each(function (parentIndex) {
		const parentItem = $(this);

		// Update inputs in parent item
		updateInputNames(parentItem, [parentIndex]);

		// Level 2: .append-child-item
		parentItem.find('.append-child-item-wrapper').each(function () {
			$(this).children('.append-child-item').each(function (childIndex) {
			const childItem = $(this);

			// Update inputs in child item
			updateInputNames(childItem, [parentIndex, childIndex]);

			// Level 3: .append-inner-child-item (optional)
			childItem.find('.append-inner-child-item-wrapper').each(function () {
				$(this).children('.append-inner-child-item').each(function (innerIndex) {
				const innerItem = $(this);

				// Update inputs in inner item
				updateInputNames(innerItem, [parentIndex, childIndex, innerIndex]);
				});
			});
			});
		});
		});
	});
	}

	function updateInputNames(scope, indexPath, increment = 0) {
		scope.find('[name]').each(function () {
			let originalName = $(this).attr('name');
			if (!originalName) return;

			let parts = [];
			let regex = /([^\[\]]+)/g;
			let match;
			while ((match = regex.exec(originalName)) !== null) {
			parts.push(match[1]);
			}
			// parts example:
			// ["platform_info", "0", "platform_profile_info", "0", "email"]

			let rebuilt = parts[0]; // root key, e.g. "platform_info"
			let idxPos = 0; // position in indexPath

			for (let i = 1; i < parts.length; i++) {
			const part = parts[i];
			if (isNaN(part)) {
				// key string like platform_profile_info or email
				rebuilt += `[${part}]`;
			} else {
				// numeric index: convert to int and adjust by increment param
				let idx = parseInt(indexPath[idxPos], 10);
				if (!isNaN(idx)) {
				idx += increment;
				} else {
				// fallback, if indexPath missing index, keep original as number
				idx = parseInt(part, 10) + increment;
				}
				rebuilt += `[${idx}]`;
				idxPos++;
			}
			}

			$(this).attr('name', rebuilt);
		});
	}








</script>