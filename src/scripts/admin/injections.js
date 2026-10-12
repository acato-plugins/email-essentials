import { siblings } from './dom';

/**
 * Get the bare email address from an RFC address ("Name <email>").
 *
 * @param {string} rfc The address.
 * @return {string}
 */
const getEmail = (rfc) => {
  rfc = rfc.split( '<' );
  if (rfc.length < 2) {
    rfc.unshift( '' );
  }
  return rfc[1].split( '>' )[0];
};

/**
 * Create an element with (server-sanitized) HTML content.
 *
 * @param {string} tag The tag name.
 * @param {string} className The class name.
 * @param {string} html The HTML content.
 * @return {HTMLElement}
 */
const createElement = (tag, className, html) => Object.assign( document.createElement( tag ), { className, innerHTML: html } );

/**
 * Hints in other admin screens: Settings > General and the Contact Form 7 mail tab.
 *
 * @param {Object} data Data passed from PHP.
 */
export default (data) => {
  if (data.adminEmailNotice) {
    document.querySelectorAll( '#admin_email,#new_admin_email' ).forEach( (input) => input.after( createElement( 'p', 'description', data.adminEmailNotice ) ) );
  }

  if (!data.contactForm7) {
    return;
  }

  const { notice, defaultFrom } = data.contactForm7;
  const host = document.location.host.replace( /^www\./, '' );
  const defaultIsLocal = defaultFrom.includes( '@' + host );

  const atdottify = (rfc, email) => rfc.replace( email, email.replace( '@', '-at-' ).replace( /\./g, '-dot-' ) + '@' + host );
  const noreplyify = (rfc, email) => rfc.replace( email, 'noreply@' + host );
  const defaultify = (rfc, email) => defaultIsLocal ? rfc.replace( email, defaultFrom ) : noreplyify( rfc, email );

  document.querySelectorAll( '#wpcf7-mail-sender,#wpcf7-mail-2-sender' ).forEach( (sender) => {
    // Fill the sample addresses in the hint from the sender field.
    const update = () => {
      const ems = siblings( sender, '.config-error' ).flatMap( (error) => [ ...error.querySelectorAll( '.wpes-err-add em' ) ] );
      const value = sender.value;
      const email = getEmail( value );
      ems.find( (em) => em.classList.contains( 'default' ) )?.replaceChildren( defaultify( value, email ) );
      ems.find( (em) => em.classList.contains( 'noreply' ) )?.replaceChildren( noreplyify( value, email ) );
      ems.find( (em) => em.classList.contains( 'at-' ) )?.replaceChildren( atdottify( value, email ) );
      ems[1]?.replaceChildren( value );
    };

    // Add the hint to every config error next to the field.
    const addHints = () => {
      siblings( sender, '.config-error' )
        .filter( (error) => !error.querySelector( '.wpes-err-add' ) )
        .forEach( (error) => {
          if ('UL' === error.tagName) {
            error.append( createElement( 'li', 'wpes-err-add', notice ) );
          } else {
            error.append( document.createElement( 'br' ), createElement( 'span', 'wpes-err-add', notice ) );
          }
          error.querySelectorAll( '.wpes-err-add em' ).forEach( (em) => em.classList.add( 'quote' ) );
          update();
        } );
    };

    sender.addEventListener( 'input', update );

    // Contact Form 7 renders and re-renders its config errors with JS; add the hint whenever they (re)appear.
    new MutationObserver( addHints ).observe( sender.parentElement, { childList: true, subtree: true } );
    addHints();
  } );
};
