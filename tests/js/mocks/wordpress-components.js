/**
 * Mock for @wordpress/components
 */
import { vi } from 'vitest';

export const PanelBody = ({ children, title }) => ({ type: 'PanelBody', title, children });
export const Button = ({ children, onClick, disabled, variant }) => ({
	type: 'Button',
	children,
	onClick,
	disabled,
	variant,
});
export const Spinner = () => ({ type: 'Spinner' });
export const ToolbarGroup = ({ children }) => children;
export const ToolbarButton = ({ children, onClick, icon, label, disabled }) => ({
	type: 'ToolbarButton',
	children,
	onClick,
	icon,
	label,
	disabled,
});
export const TextControl = vi.fn();
export const SelectControl = vi.fn();
export const ToggleControl = vi.fn();
export const Notice = vi.fn();
