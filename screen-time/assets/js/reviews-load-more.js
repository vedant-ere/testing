/**
 * Movie reviews progressive loader.
 *
 * Requests additional reviews via AJAX and appends them to the existing grid.
 */
( () => {
	const button = document.querySelector( '[data-load-more-reviews]' );
	if ( ! button ) {
		return;
	}

	let page = 2;
	const postId = button.dataset.postId || '';

	if ( ! postId || ! window.screentimeReviews ) {
		return;
	}

	const grid = document.querySelector( '.movie-review-grid' );
	if ( ! grid ) {
		return;
	}

	const i18n = window.screentimeReviews.i18n ? window.screentimeReviews.i18n : {};
	const loadingText = String( i18n.loading || 'Loading...' );
	const loadMoreText = String( i18n.loadMore || 'Load more reviews' );

	/**
	 * Restores button to idle state after request completion.
	 *
	 * @return {void} No return value.
	 */
	const resetButton = () => {
		button.disabled = false;
		button.textContent = loadMoreText;
	};

	/**
	 * Handles a load-more response payload.
	 *
	 * @param {Object} response AJAX JSON response.
	 * @return {void} No return value.
	 */
	const handleResponse = ( response ) => {
		if (
			! response ||
			! response.success ||
			! response.data ||
			! response.data.html ||
			0 === Number( response.data.count )
		) {
			button.remove();
			return;
		}

		grid.insertAdjacentHTML( 'beforeend', String( response.data.html ) );
		page += 1;
		resetButton();
	};

	/**
	 * Requests the next review page and appends it to the grid.
	 *
	 * @return {void} No return value.
	 */
	const loadMoreReviews = () => {
		button.disabled = true;
		button.textContent = loadingText;

		const data = new window.FormData();
		data.append( 'action', 'screentime_load_more_reviews' );
		data.append( 'nonce', String( window.screentimeReviews.nonce || '' ) );
		data.append( 'post_id', postId );
		data.append( 'page', String( page ) );

		window
			.fetch( String( window.screentimeReviews.ajaxUrl || '' ), {
				method: 'POST',
				credentials: 'same-origin',
				body: data,
			} )
			.then( ( response ) => response.json() )
			.then( handleResponse )
			.catch( resetButton );
	};

	button.addEventListener( 'click', loadMoreReviews );
} )();
