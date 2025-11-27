/**
 * Mock for @wordpress/data
 */
import { vi } from 'vitest';

export const useDispatch = vi.fn(() => ({
	updateBlockAttributes: vi.fn(),
}));

export const useSelect = vi.fn(() => ({}));

export const withDispatch = vi.fn(() => (Component) => Component);

export const withSelect = vi.fn(() => (Component) => Component);

export const dispatch = vi.fn(() => ({
	updateBlockAttributes: vi.fn(),
}));

export const select = vi.fn(() => ({}));
