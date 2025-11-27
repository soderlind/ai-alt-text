/**
 * Mock for @wordpress/compose
 */
import { vi } from 'vitest';

export const createHigherOrderComponent = vi.fn((fn, name) => fn);
export const compose = vi.fn((...fns) => (x) => fns.reduceRight((acc, fn) => fn(acc), x));
export const withState = vi.fn((initialState) => (Component) => Component);
