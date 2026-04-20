/**
 * Save callback for Movie block.
 *
 * Returning null keeps rendering on PHP side so updates to movie metadata are
 * reflected automatically without requiring users to re-save block content.
 *
 * @return {null} Dynamic block save output.
 */
export default function save() {
	return null;
}
