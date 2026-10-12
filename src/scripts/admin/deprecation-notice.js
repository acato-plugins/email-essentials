/**
 * Remember the dismissal of the deprecation notice.
 *
 * @param {Object} data Data passed from PHP.
 */
export default (data) => {
  let controller = null;

  // WordPress adds the dismiss button later, so listen on the notice itself.
  document.querySelectorAll( '.wpes_deprecation_notice_dismissible' ).forEach( (notice) => notice.addEventListener( 'click', (e) => {
    if (!e.target.closest( '.notice-dismiss' )) {
      return;
    }

    // A newer dismissal supersedes one still in flight.
    controller?.abort();
    controller = new AbortController();
    const timeout = setTimeout( () => controller.abort(), 10000 );

    fetch( window.ajaxurl, {
      method: 'POST',
      body: new URLSearchParams( {
        action: 'acato_email_essentials_dismiss_deprecation_notice',
        _ajax_nonce: data.dismissNonce,
      } ),
      credentials: 'same-origin',
      keepalive: true,
      signal: controller.signal,
    } )
      .catch( (error) => 'AbortError' !== error.name && console.error( error ) )
      .finally( () => clearTimeout( timeout ) );
  } ) );
};
