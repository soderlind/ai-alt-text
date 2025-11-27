<?php
/**
 * Tests for LLMClient.
 *
 * @package AiAltText\Tests
 */

declare(strict_types=1);

namespace AiAltText\Tests;

use AiAltText\AI\LLMClient;
use MockWordPress;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Test case for LLMClient.
 */
class LLMClientTest extends TestCase {

	protected function setUp(): void {
		MockWordPress::reset();
	}

	public function testAnalyzeImageThrowsForUnknownProvider(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'  => 'unknown_provider',
			'openai_key'   => 'test-key',
			'openai_model' => 'gpt-4o',
		];

		$client = new LLMClient();

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'Unknown AI provider: unknown_provider' );

		$client->analyzeImage( 'http://example.com/image.jpg', 'Describe this image' );
	}

	public function testAnalyzeImageWithOpenAISuccess(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'  => 'openai',
			'openai_type'  => 'openai',
			'openai_key'   => 'sk-test-key',
			'openai_model' => 'gpt-4o',
		];

		MockWordPress::$http_responses[ 'api.openai.com' ] = [
			'choices' => [
				[
					'message' => [
						'content' => 'A cat sitting on a windowsill',
					],
				],
			],
		];

		$client = new LLMClient();
		$result = $client->analyzeImage( 'http://example.com/cat.jpg', 'Describe this image' );

		$this->assertEquals( 'A cat sitting on a windowsill', $result );
	}

	public function testAnalyzeImageWithOpenAIThrowsOnMissingKey(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'  => 'openai',
			'openai_type'  => 'openai',
			'openai_key'   => '',
			'openai_model' => 'gpt-4o',
		];

		$client = new LLMClient();

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'OpenAI configuration is incomplete' );

		$client->analyzeImage( 'http://example.com/image.jpg', 'Describe this image' );
	}

	public function testAnalyzeImageWithAnthropicSuccess(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'     => 'anthropic',
			'anthropic_key'   => 'sk-ant-test',
			'anthropic_model' => 'claude-3-5-sonnet-latest',
		];

		MockWordPress::$http_responses[ 'api.anthropic.com' ] = [
			'content' => [
				[
					'text' => 'A dog playing in the park',
				],
			],
		];

		MockWordPress::$http_responses[ 'example.com/dog.jpg' ] = 'fake-image-data';

		$client = new LLMClient();
		$result = $client->analyzeImage( 'http://example.com/dog.jpg', 'Describe this image' );

		$this->assertEquals( 'A dog playing in the park', $result );
	}

	public function testAnalyzeImageWithAnthropicThrowsOnMissingKey(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'     => 'anthropic',
			'anthropic_key'   => '',
			'anthropic_model' => 'claude-3-5-sonnet-latest',
		];

		$client = new LLMClient();

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'Anthropic configuration is incomplete' );

		$client->analyzeImage( 'http://example.com/image.jpg', 'Describe this image' );
	}

	public function testAnalyzeImageWithGeminiSuccess(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'  => 'gemini',
			'gemini_key'   => 'gemini-test-key',
			'gemini_model' => 'gemini-2.0-flash',
		];

		MockWordPress::$http_responses[ 'generativelanguage.googleapis.com' ] = [
			'candidates' => [
				[
					'content' => [
						'parts' => [
							[
								'text' => 'A mountain landscape at sunset',
							],
						],
					],
				],
			],
		];

		MockWordPress::$http_responses[ 'example.com/mountain.jpg' ] = 'fake-image-data';

		$client = new LLMClient();
		$result = $client->analyzeImage( 'http://example.com/mountain.jpg', 'Describe this image' );

		$this->assertEquals( 'A mountain landscape at sunset', $result );
	}

	public function testAnalyzeImageWithGeminiThrowsOnMissingKey(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'  => 'gemini',
			'gemini_key'   => '',
			'gemini_model' => 'gemini-2.0-flash',
		];

		$client = new LLMClient();

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'Gemini configuration is incomplete' );

		$client->analyzeImage( 'http://example.com/image.jpg', 'Describe this image' );
	}

	public function testAnalyzeImageWithOllamaSuccess(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'     => 'ollama',
			'ollama_endpoint' => 'http://localhost:11434',
			'ollama_model'    => 'llava',
		];

		MockWordPress::$http_responses[ 'localhost:11434' ] = [
			'response' => 'A bird flying over the ocean',
		];

		MockWordPress::$http_responses[ 'example.com/bird.jpg' ] = 'fake-image-data';

		$client = new LLMClient();
		$result = $client->analyzeImage( 'http://example.com/bird.jpg', 'Describe this image' );

		$this->assertEquals( 'A bird flying over the ocean', $result );
	}

	public function testAnalyzeImageWithOllamaHandlesEmptyResponse(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'     => 'ollama',
			'ollama_endpoint' => 'http://localhost:11434',
			'ollama_model'    => 'llava',
		];

		// Empty response from Ollama
		MockWordPress::$http_responses[ 'localhost:11434' ] = [
			'response' => '',
		];

		MockWordPress::$http_responses[ 'example.com/image.jpg' ] = 'fake-image-data';

		$client = new LLMClient();

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'Empty response from Ollama' );

		$client->analyzeImage( 'http://example.com/image.jpg', 'Describe this image' );
	}

	public function testAnalyzeImageWithGrokSuccess(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider' => 'grok',
			'grok_key'    => 'xai-test-key',
			'grok_model'  => 'grok-2-vision-latest',
		];

		MockWordPress::$http_responses[ 'api.x.ai' ] = [
			'choices' => [
				[
					'message' => [
						'content' => 'A robot waving hello',
					],
				],
			],
		];

		$client = new LLMClient();
		$result = $client->analyzeImage( 'http://example.com/robot.jpg', 'Describe this image' );

		$this->assertEquals( 'A robot waving hello', $result );
	}

	public function testAnalyzeImageWithGrokThrowsOnMissingKey(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider' => 'grok',
			'grok_key'    => '',
			'grok_model'  => 'grok-2-vision-latest',
		];

		$client = new LLMClient();

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'Grok configuration is incomplete' );

		$client->analyzeImage( 'http://example.com/image.jpg', 'Describe this image' );
	}

	public function testAnalyzeImageWithAzureOpenAISuccess(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'       => 'openai',
			'openai_type'       => 'azure',
			'openai_key'        => 'azure-test-key',
			'openai_model'      => 'gpt-4o-deployment',
			'azure_endpoint'    => 'https://myresource.openai.azure.com',
			'azure_api_version' => '2024-02-15-preview',
		];

		MockWordPress::$http_responses[ 'openai.azure.com' ] = [
			'choices' => [
				[
					'message' => [
						'content' => 'An office building at night',
					],
				],
			],
		];

		$client = new LLMClient();
		$result = $client->analyzeImage( 'http://example.com/building.jpg', 'Describe this image' );

		$this->assertEquals( 'An office building at night', $result );
	}

	public function testAnalyzeImageWithAzureThrowsOnMissingEndpoint(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'    => 'openai',
			'openai_type'    => 'azure',
			'openai_key'     => 'azure-test-key',
			'openai_model'   => 'gpt-4o',
			'azure_endpoint' => '',
		];

		$client = new LLMClient();

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'Azure OpenAI endpoint is not configured' );

		$client->analyzeImage( 'http://example.com/image.jpg', 'Describe this image' );
	}

	public function testParseOpenAIResponseHandlesApiError(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'  => 'openai',
			'openai_type'  => 'openai',
			'openai_key'   => 'sk-test-key',
			'openai_model' => 'gpt-4o',
		];

		MockWordPress::$http_responses[ 'api.openai.com' ] = [
			'error' => [
				'message' => 'Rate limit exceeded',
			],
		];

		$client = new LLMClient();

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'Rate limit exceeded' );

		$client->analyzeImage( 'http://example.com/image.jpg', 'Describe this image' );
	}

	public function testParseOpenAIResponseHandlesEmptyResponse(): void {
		MockWordPress::$options[ 'ai_alt_text_options' ] = [
			'ai_provider'  => 'openai',
			'openai_type'  => 'openai',
			'openai_key'   => 'sk-test-key',
			'openai_model' => 'gpt-4o',
		];

		MockWordPress::$http_responses[ 'api.openai.com' ] = [
			'choices' => [
				[
					'message' => [
						'content' => '',
					],
				],
			],
		];

		$client = new LLMClient();

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'Empty response from model' );

		$client->analyzeImage( 'http://example.com/image.jpg', 'Describe this image' );
	}
}
