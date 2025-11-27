/**
 * Mock for @wordpress/hooks
 */
import { vi } from 'vitest';

export const addFilter = vi.fn();
export const removeFilter = vi.fn();
export const applyFilters = vi.fn((name, value) => value);
export const addAction = vi.fn();
export const removeAction = vi.fn();
export const doAction = vi.fn();
