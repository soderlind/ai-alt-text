/**
 * Mock for @wordpress/i18n
 */
import { vi } from 'vitest';

export const __ = vi.fn((text) => text);
export const _x = vi.fn((text) => text);
export const _n = vi.fn((single, plural, number) => (number === 1 ? single : plural));
export const sprintf = vi.fn((format, ...args) => {
	let result = format;
	args.forEach((arg) => {
		result = result.replace(/%s|%d/, arg);
	});
	return result;
});
