/**
 * Parse "/pattern/flags" into a RegExp; null when it isn't one or doesn't compile.
 *
 * @param {string} value The field value.
 * @return {RegExp|null}
 */
const parseRegExp = (value) => {
  const match = /^\/([\s\S]+)\/(i?)$/.exec( value );
  if (!match) {
    return null;
  }
  try {
    return new RegExp( match[1], match[2] );
  } catch {
    return null;
  }
};

/**
 * Admins panel.
 */
export default () => {
  if (!document.querySelector( '.wpes-admins' )) {
    return;
  }

  const failures = document.querySelectorAll( '.a-fail' );
  const fields = document.querySelectorAll( '.a-regexp' );

  // Highlight the failed subjects the typed regular expression matches.
  const testFailures = (field) => {
    const re = parseRegExp( field.value );
    failures.forEach( (failure) => failure.classList.toggle( 'match', !!re && re.test( failure.textContent ) ) );
  };

  fields.forEach( (field) => {
    field.addEventListener( 'blur', () => {
      const valid = !!parseRegExp( field.value );
      field.classList.toggle( 'error', '' !== field.value && !valid );
      field.classList.toggle( 'match', valid );
    } );
    field.addEventListener( 'focus', () => {
      fields.forEach( (other) => other.classList.remove( 'match' ) );
      field.classList.remove( 'error' );
      testFailures( field );
    } );
    field.addEventListener( 'input', () => testFailures( field ) );
  } );
};
