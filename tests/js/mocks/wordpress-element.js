/**
 * Mock for @wordpress/element
 */
import { vi } from 'vitest';

export const Fragment = ({ children }) => children;
export const useState = vi.fn((initial) => [initial, vi.fn()]);
export const useEffect = vi.fn();
export const useCallback = vi.fn((fn) => fn);
export const useMemo = vi.fn((fn) => fn());
export const useRef = vi.fn((initial) => ({ current: initial }));
export const createElement = vi.fn((type, props, ...children) => ({
	type,
	props: { ...props, children },
}));
export const render = vi.fn();
export const createPortal = vi.fn((children) => children);
