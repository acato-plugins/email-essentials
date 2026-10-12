/**
 * Remove all show-* classes from an element.
 *
 * @param {HTMLElement} element The element.
 */
const removeShowClasses = (element) => element.classList.remove( ...[ ...element.classList ].filter( (name) => name.startsWith( 'show-' ) ) );

/**
 * Clicking a row cycles through these views, starting at body.
 */
const nextView = {
  'show-body': 'body-source',
  'show-body-source': 'headers',
  'show-headers': 'alt-body',
  'show-alt-body': 'debug',
};

/**
 * The data panel belonging to an email row.
 *
 * @param {HTMLElement} item The email row.
 * @return {HTMLElement|null}
 */
const getEmailData = (item) => document.getElementById( item.id.replace( 'email-', 'email-data-' ) );

/**
 * Emails panel.
 */
export default () => {
  if (!document.querySelector( '.wpes-emails' )) {
    return;
  }

  const mailTabs = document.querySelectorAll( '.mail-tab' );
  const viewer = document.getElementById( 'mail-viewer' );
  let activeEmail = document.querySelector( '.email-item.active' );

  // Switch the active email to a specific view.
  const switchToView = (view) => {
    if (!activeEmail) {
      return;
    }

    [ activeEmail, getEmailData( activeEmail ) ].filter( Boolean ).forEach( (element) => {
      removeShowClasses( element );
      element.classList.add( 'show-' + view );
    } );

    mailTabs.forEach( (tab) => {
      const active = tab.dataset.view === view;
      tab.classList.toggle( 'active', active );
      tab.setAttribute( 'aria-selected', String( active ) );
    } );
  };

  mailTabs.forEach( (tab) => tab.addEventListener( 'click', (e) => {
    e.preventDefault();
    switchToView( tab.dataset.view );
  } ) );

  // One delegated listener for all rows.
  document.querySelector( '.wpes-email-history' )?.addEventListener( 'click', (e) => {
    const item = e.target.closest( '.email-item' );
    if (!item || e.target.closest( 'a.dashicons-download' )) {
      return;
    }

    // Only the previously active row and its data panel carry state.
    if (activeEmail && activeEmail !== item) {
      activeEmail.classList.remove( 'active' );
      removeShowClasses( activeEmail );
      const previousData = getEmailData( activeEmail );
      if (previousData) {
        removeShowClasses( previousData );
      }
    }
    activeEmail = item;
    item.classList.add( 'active' );

    const current = Object.keys( nextView ).find( (name) => item.classList.contains( name ) );
    if (viewer) {
      viewer.hidden = false;
    }
    switchToView( current ? nextView[current] : 'body' );
  } );

  document.querySelectorAll( '#wpes-page-size, #wpes-page-size-bottom' ).forEach( (select) => select.addEventListener( 'change', () => {
    const currentUrl = new URL( window.location.href );
    currentUrl.searchParams.set( '_limit', select.value );
    currentUrl.searchParams.set( '_page', '0' ); // Reset to first page
    window.location.href = currentUrl.toString();
  } ) );
};
