/**
 * Save callback for Movies block.
 *
 * Returning null keeps frontend HTML server-rendered so changes in post data
 * are reflected without re-saving posts that contain this block.
 *
 * @returns {null} Dynamic block save output.
 */
export default function save() {
	return null;
}
