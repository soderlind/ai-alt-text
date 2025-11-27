<?php
/**
 * Tests for AltTextService.
 *
 * @package AiAltText\Tests
 */

declare(strict_types=1);

namespace AiAltText\Tests;

use AiAltText\Services\AltTextService;
use MockWordPress;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Test case for AltTextService.
 */
class AltTextServiceTest extends TestCase {

	protected function setUp(): void {
		MockWordPress::reset();
	}

	public function testGetLanguageReturnsEnglishForEnUsLocale(): void {
		MockWordPress::$locale = 'en_US';

		// Use reflection to access private method
		$service = new AltTextService();
		$method  = new \ReflectionMethod( AltTextService::class, 'getLanguage' );
		$method->setAccessible( true );

		$result = $method->invoke( $service );

		$this->assertEquals( 'English', $result );
	}

	public function testGetLanguageReturnsNorwegianForNbNoLocale(): void {
		MockWordPress::$locale = 'nb_NO';

		$service = new AltTextService();
		$method  = new \ReflectionMethod( AltTextService::class, 'getLanguage' );
		$method->setAccessible( true );

		$result = $method->invoke( $service );

		$this->assertEquals( 'Norwegian', $result );
	}

	public function testGetLanguageReturnsGermanForDeDeLocale(): void {
		MockWordPress::$locale = 'de_DE';

		$service = new AltTextService();
		$method  = new \ReflectionMethod( AltTextService::class, 'getLanguage' );
		$method->setAccessible( true );

		$result = $method->invoke( $service );

		$this->assertEquals( 'German', $result );
	}

	public function testGetLanguageReturnsFrenchForFrFrLocale(): void {
		MockWordPress::$locale = 'fr_FR';

		$service = new AltTextService();
		$method  = new \ReflectionMethod( AltTextService::class, 'getLanguage' );
		$method->setAccessible( true );

		$result = $method->invoke( $service );

		$this->assertEquals( 'French', $result );
	}

	public function testGetLanguageReturnsEnglishForUnknownLocale(): void {
		MockWordPress::$locale = 'xx_XX';

		$service = new AltTextService();
		$method  = new \ReflectionMethod( AltTextService::class, 'getLanguage' );
		$method->setAccessible( true );

		$result = $method->invoke( $service );

		$this->assertEquals( 'English', $result );
	}

	public function testGetLanguageMatchesPartialLocale(): void {
		MockWordPress::$locale = 'de_AT'; // German (Austria)

		$service = new AltTextService();
		$method  = new \ReflectionMethod( AltTextService::class, 'getLanguage' );
		$method->setAccessible( true );

		$result = $method->invoke( $service );

		$this->assertEquals( 'German', $result );
	}

	public function testBuildPromptIncludesLanguage(): void {
		$service = new AltTextService();
		$method  = new \ReflectionMethod( AltTextService::class, 'buildPrompt' );
		$method->setAccessible( true );

		$prompt = $method->invoke( $service, 'Norwegian' );

		$this->assertStringContainsString( 'Norwegian', $prompt );
		$this->assertStringContainsString( 'alt text', $prompt );
		$this->assertStringContainsString( 'screen readers', $prompt );
	}

	public function testUpdateAttachmentAltTextSavesToMeta(): void {
		$service       = new AltTextService();
		$attachment_id = 123;
		$alt_text      = 'A beautiful sunset over the ocean';

		$result = $service->updateAttachmentAltText( $attachment_id, $alt_text );

		$this->assertTrue( $result );
		$this->assertEquals(
			$alt_text,
			MockWordPress::$post_meta[ $attachment_id ][ '_wp_attachment_image_alt' ][ 0 ]
		);
	}

	public function testGenerateForAttachmentThrowsForNonImage(): void {
		$service = new AltTextService();

		// Attachment exists but is not an image
		MockWordPress::$attachments[ 123 ] = [
			'is_image' => false,
			'url'      => 'http://example.com/doc.pdf',
		];

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'Attachment is not an image' );

		$service->generateForAttachment( 123 );
	}

	public function testGenerateForAttachmentThrowsForMissingUrl(): void {
		$service = new AltTextService();

		// Attachment is an image but has no URL
		MockWordPress::$attachments[ 123 ] = [
			'is_image' => true,
			'url'      => false,
		];

		$this->expectException( RuntimeException::class);
		$this->expectExceptionMessage( 'Could not get attachment URL' );

		$service->generateForAttachment( 123 );
	}

	public function testGenerateAndSaveReturnsExistingAltTextWhenNoOverwrite(): void {
		$service       = new AltTextService();
		$attachment_id = 123;
		$existing_alt  = 'Existing alt text';

		MockWordPress::$attachments[ $attachment_id ] = [
			'is_image' => true,
			'url'      => 'http://example.com/image.jpg',
		];

		MockWordPress::$post_meta[ $attachment_id ] = [
			'_wp_attachment_image_alt' => [ $existing_alt ],
		];

		$result = $service->generateAndSave( $attachment_id, false );

		$this->assertEquals( $existing_alt, $result );
	}
}
