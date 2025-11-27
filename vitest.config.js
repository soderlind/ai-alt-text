import { defineConfig } from 'vitest/config';
import path from 'path';

export default defineConfig({
	resolve: {
		alias: {
			'@wordpress/hooks': path.resolve(__dirname, 'tests/js/mocks/wordpress-hooks.js'),
			'@wordpress/compose': path.resolve(__dirname, 'tests/js/mocks/wordpress-compose.js'),
			'@wordpress/element': path.resolve(__dirname, 'tests/js/mocks/wordpress-element.js'),
			'@wordpress/block-editor': path.resolve(__dirname, 'tests/js/mocks/wordpress-block-editor.js'),
			'@wordpress/components': path.resolve(__dirname, 'tests/js/mocks/wordpress-components.js'),
			'@wordpress/i18n': path.resolve(__dirname, 'tests/js/mocks/wordpress-i18n.js'),
			'@wordpress/api-fetch': path.resolve(__dirname, 'tests/js/mocks/wordpress-api-fetch.js'),
			'@wordpress/data': path.resolve(__dirname, 'tests/js/mocks/wordpress-data.js'),
		},
	},
	test: {
		environment: 'jsdom',
		globals: true,
		setupFiles: ['./tests/js/setup.js'],
		include: ['tests/js/**/*.test.js'],
		coverage: {
			provider: 'v8',
			reporter: ['text', 'json', 'html'],
			include: ['src/block/**/*.js'],
		},
	},
});
