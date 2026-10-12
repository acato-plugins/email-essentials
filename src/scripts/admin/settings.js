import { toggle } from './dom';

/**
 * Settings panel.
 */
export default () => {
  if (!document.querySelector( '.wpes-settings' )) {
    return;
  }

  // Show/hide the .on-{id} and .not-{id} parts of a setting.
  [ 'enable_history', 'smtp-enabled', 'enable-smime', 'enable-dkim', 'smtp-is_html', 'tracker_enabled' ].forEach( (id) => {
    const input = document.getElementById( id );
    if (!input) {
      return;
    }
    const on = document.querySelectorAll( `.on-${ id }` );
    const off = document.querySelectorAll( `.not-${ id }` );
    const update = () => {
      on.forEach( (element) => toggle( element, input.checked ) );
      off.forEach( (element) => toggle( element, !input.checked ) );
    };
    input.addEventListener( 'change', update );
    update();
  } );

  // Show a label when its field matches a regular expression.
  const checkables = [];
  document.querySelectorAll( '.on-regexp-test' ).forEach( (label) => {
    const field = document.getElementById( label.dataset.field );
    if (!field) {
      return;
    }
    const regexp = new RegExp( label.dataset.regexp, 'i' );
    const checkable = [ 'checkbox', 'radio' ].includes( field.type );
    const update = () => toggle( label, ( !checkable || field.checked ) && regexp.test( field.value ) );

    if (checkable) {
      // Checking a radio unchecks the others without a change event of their own.
      checkables.push( { name: field.name, update } );
      field.addEventListener( 'change', () => checkables.filter( (item) => item.name === field.name ).forEach( (item) => item.update() ) );
    } else {
      field.addEventListener( 'input', update );
    }
    update();
  } );

  // Show the default port of the chosen encryption as placeholder.
  const smtpSecure = document.getElementById( 'smtp-secure' );
  const smtpPort = document.getElementById( 'smtp-port' );
  if (smtpSecure && smtpPort) {
    const update = () => smtpPort.placeholder = smtpSecure.selectedOptions[0]?.dataset.smtpPort || '';
    smtpSecure.addEventListener( 'change', update );
    update();
  }

  // Scroll to the debug info panel.
  document.getElementById( 'wpes-debug-info' )?.scrollIntoView( { behavior: 'smooth' } );
};
