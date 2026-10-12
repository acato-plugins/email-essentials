import admins from './admin/admins';
import deprecationNotice from './admin/deprecation-notice';
import emails from './admin/emails';
import injections from './admin/injections';
import samples from './admin/samples';
import settings from './admin/settings';

const init = () => {
  const data = window.acatoEmailEssentials || {};

  settings();
  samples( data );
  emails();
  admins();
  injections( data );
  deprecationNotice( data );
};

if ('loading' === document.readyState) {
  document.addEventListener( 'DOMContentLoaded', init );
} else {
  init();
}
