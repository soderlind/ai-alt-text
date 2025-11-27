/**
 * Mock for @wordpress/block-editor
 */
import { vi } from 'vitest';

export const InspectorControls = ({ children }) => children;
export const BlockControls = ({ children }) => children;
export const RichText = vi.fn();
export const useBlockProps = vi.fn(() => ({}));
export const InnerBlocks = vi.fn();
