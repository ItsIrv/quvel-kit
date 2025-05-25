/**
 * Validates a hostname.
 * @param hostname - The hostname to validate.
 * @returns True if the hostname is valid, false otherwise.
 */
export function isValidHostname(hostname: string): boolean {
  if (!hostname || hostname.length > 253) return false;

  return hostname
    .split('.')
    .every((label) => /^[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/.test(label));
}
