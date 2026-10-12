import { preventSubmit } from './dom';

/**
 * S/MIME and DKIM sample filenames, scripts and DNS records, updated as the user types.
 *
 * @param {Object} data Data passed from PHP.
 */
export default (data) => {
  // S/MIME: filenames for a sample email address.
  const smimeInput = document.getElementById( 'smime-sample-email' );
  if (smimeInput) {
    const outputs = document.querySelectorAll( '.smime-sample-email-output' );
    smimeInput.addEventListener( 'input', () => {
      const email = smimeInput.value.replace( /[^a-z0-9-_.@]/gi, '_' );
      outputs.forEach( (output) => output.textContent = output.dataset.pattern.replace( '?', () => email || output.dataset.default ) );
    } );
    smimeInput.addEventListener( 'keydown', preventSubmit );
  }

  // DKIM: the outputs' text becomes a pattern with ?0, ?1, ?2 in place of the sample values.
  const inputs = document.querySelectorAll( '.dkim-sample-input' );
  if (!inputs.length) {
    return;
  }

  const defaults = data.dkimSamples || [];
  const keyNumbers = { domain: 0, domainkey: 1, password: 2 };
  const sampleData = [ ...defaults ];
  const outputs = document.querySelectorAll( '.dkim-sample-output' );

  outputs.forEach( (output) => {
    let pattern = output.textContent;
    defaults.forEach( (value, key) => pattern = pattern.replaceAll( value, '?' + key ) );
    output.dataset.pattern = pattern;
  } );

  inputs.forEach( (input) => {
    const key = keyNumbers[input.id.replace( 'dkim-sample-', '' )];
    input.addEventListener( 'input', () => {
      sampleData[key] = input.value || defaults[key];
      outputs.forEach( (output) => {
        let text = output.dataset.pattern;
        sampleData.forEach( (value, index) => text = text.replaceAll( '?' + index, () => value ) );
        output.textContent = text;
      } );
    } );
    input.addEventListener( 'keydown', preventSubmit );
  } );
};
