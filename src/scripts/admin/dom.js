/**
 * Show or hide an element, like jQuery's toggle( state ).
 *
 * @param {HTMLElement} element The element.
 * @param {boolean} visible Show (true) or hide (false).
 */
export const toggle = (element, visible) => {
  element.hidden = !visible;
};

/**
 * The siblings of an element matching a selector.
 *
 * @param {HTMLElement} element The element.
 * @param {string} selector The selector to match.
 * @return {HTMLElement[]}
 */
export const siblings = (element, selector) => [ ...element.parentElement.children ].filter( (sibling) => sibling !== element && sibling.matches( selector ) );

/**
 * Prevent Enter from submitting the form.
 *
 * @param {KeyboardEvent} e The keypress event.
 */
export const preventSubmit = (e) => 'Enter' === e.key && e.preventDefault();
