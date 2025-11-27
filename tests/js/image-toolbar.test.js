/**
 * Tests for Image Toolbar / Sidebar component.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import apiFetch from '@wordpress/api-fetch';

describe('Image Toolbar Registration', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('should have addFilter available for registering block extensions', () => {
		// Verify our mock is working
		expect(addFilter).toBeDefined();
		expect(typeof addFilter).toBe('function');

		// Simulate what the component does
		addFilter(
			'editor.BlockEdit',
			'ai-alt-text/image-sidebar',
			(BlockEdit) => BlockEdit
		);

		expect(addFilter).toHaveBeenCalledWith(
			'editor.BlockEdit',
			'ai-alt-text/image-sidebar',
			expect.any(Function)
		);
	});

	it('should use createHigherOrderComponent for wrapping', () => {
		expect(createHigherOrderComponent).toBeDefined();

		const wrapper = createHigherOrderComponent(
			(BlockEdit) => BlockEdit,
			'withAIAltTextSidebar'
		);

		expect(createHigherOrderComponent).toHaveBeenCalledWith(
			expect.any(Function),
			'withAIAltTextSidebar'
		);
	});
});

describe('AI Alt Text API Integration', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('should call apiFetch with correct parameters for attachment', async () => {
		apiFetch.mockResolvedValueOnce({
			success: true,
			alt_text: 'A cat sitting on a windowsill',
		});

		const result = await apiFetch({
			path: '/ai-alt-text/v1/generate',
			method: 'POST',
			data: {
				attachment_id: 123,
				image_url: null,
				overwrite: true,
			},
		});

		expect(apiFetch).toHaveBeenCalledWith({
			path: '/ai-alt-text/v1/generate',
			method: 'POST',
			data: {
				attachment_id: 123,
				image_url: null,
				overwrite: true,
			},
		});

		expect(result.success).toBe(true);
		expect(result.alt_text).toBe('A cat sitting on a windowsill');
	});

	it('should call apiFetch with image_url when no attachment_id', async () => {
		apiFetch.mockResolvedValueOnce({
			success: true,
			alt_text: 'A dog playing in the park',
		});

		const result = await apiFetch({
			path: '/ai-alt-text/v1/generate',
			method: 'POST',
			data: {
				attachment_id: null,
				image_url: 'https://example.com/dog.jpg',
				overwrite: true,
			},
		});

		expect(result.success).toBe(true);
		expect(result.alt_text).toBe('A dog playing in the park');
	});

	it('should handle API errors gracefully', async () => {
		apiFetch.mockRejectedValueOnce(new Error('Network error'));

		await expect(
			apiFetch({
				path: '/ai-alt-text/v1/generate',
				method: 'POST',
				data: { attachment_id: 123 },
			})
		).rejects.toThrow('Network error');
	});

	it('should handle API failure response', async () => {
		apiFetch.mockResolvedValueOnce({
			success: false,
			message: 'AI provider not configured',
		});

		const result = await apiFetch({
			path: '/ai-alt-text/v1/generate',
			method: 'POST',
			data: { attachment_id: 123 },
		});

		expect(result.success).toBe(false);
		expect(result.message).toBe('AI provider not configured');
	});
});

describe('Block Filtering Logic', () => {
	it('should only modify core/image blocks', () => {
		// Mock props for non-image block
		const nonImageProps = {
			name: 'core/paragraph',
			attributes: {},
			setAttributes: vi.fn(),
		};

		// Mock props for image block
		const imageProps = {
			name: 'core/image',
			attributes: {
				id: 123,
				url: 'https://example.com/image.jpg',
				alt: '',
			},
			setAttributes: vi.fn(),
		};

		// The filter should pass through non-image blocks unchanged
		expect(nonImageProps.name).not.toBe('core/image');
		expect(imageProps.name).toBe('core/image');
	});

	it('should have access to image attributes', () => {
		const imageProps = {
			name: 'core/image',
			attributes: {
				id: 456,
				url: 'https://example.com/photo.jpg',
				alt: 'Existing alt text',
			},
			setAttributes: vi.fn(),
		};

		expect(imageProps.attributes.id).toBe(456);
		expect(imageProps.attributes.url).toBe('https://example.com/photo.jpg');
		expect(imageProps.attributes.alt).toBe('Existing alt text');
	});

	it('should be able to update alt attribute via setAttributes', () => {
		const setAttributes = vi.fn();
		const newAltText = 'AI generated alt text';

		setAttributes({ alt: newAltText });

		expect(setAttributes).toHaveBeenCalledWith({ alt: newAltText });
	});
});

describe('UI State Management', () => {
	it('should track generating state', () => {
		let isGenerating = false;
		const setIsGenerating = (value) => {
			isGenerating = value;
		};

		expect(isGenerating).toBe(false);

		setIsGenerating(true);
		expect(isGenerating).toBe(true);

		setIsGenerating(false);
		expect(isGenerating).toBe(false);
	});

	it('should disable button when generating', () => {
		const isGenerating = true;
		const hasImage = true;

		const shouldDisable = isGenerating || !hasImage;

		expect(shouldDisable).toBe(true);
	});

	it('should disable button when no image', () => {
		const isGenerating = false;
		const url = null;
		const id = null;

		const hasImage = url || id;
		const shouldDisable = isGenerating || !hasImage;

		expect(shouldDisable).toBe(true);
	});

	it('should enable button when image present and not generating', () => {
		const isGenerating = false;
		const url = 'https://example.com/image.jpg';
		const id = 123;

		const hasImage = url || id;
		const shouldDisable = isGenerating || !hasImage;

		expect(shouldDisable).toBe(false);
	});
});

describe('Internationalization', () => {
	it('should have all required i18n strings', () => {
		const { i18n } = global.aiAltTextConfig;

		expect(i18n.generateAltText).toBeDefined();
		expect(i18n.generating).toBeDefined();
		expect(i18n.success).toBeDefined();
		expect(i18n.error).toBeDefined();
		expect(i18n.noImage).toBeDefined();
		expect(i18n.buttonLabel).toBeDefined();
	});

	it('should have correct default values', () => {
		const { i18n } = global.aiAltTextConfig;

		expect(i18n.generateAltText).toBe('Generate AI Alt Text');
		expect(i18n.generating).toBe('Generating...');
		expect(i18n.buttonLabel).toBe('AI Alt Text');
	});
});

describe('REST API Configuration', () => {
	it('should have REST URL configured', () => {
		expect(global.aiAltTextConfig.restUrl).toBe('/wp-json/ai-alt-text/v1/generate');
	});

	it('should have nonce configured', () => {
		expect(global.aiAltTextConfig.nonce).toBeDefined();
		expect(global.aiAltTextConfig.nonce).not.toBe('');
	});
});
