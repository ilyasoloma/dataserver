<?php
/*
    ***** BEGIN LICENSE BLOCK *****
    
    This file is part of the Zotero Data Server.
    
    Copyright © 2020 Corporation for Digital Scholarship
                     Vienna, Virginia, USA
                     https://www.zotero.org
    
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    ***** END LICENSE BLOCK *****
*/

namespace APIv3;
use API3 as API;
require_once 'APITests.inc.php';
require_once 'include/api3.inc.php';

class AnnotationTest extends APITests {
	private static $attachmentKey = null;
	private static $attachmentJSON = null;
	
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		API::userClear(self::$config['userID']);
		API::groupClear(self::$config['ownedPrivateGroupID']);
		
		$key = API::createItem("book", false, null, 'key');
		self::$attachmentJSON = API::createAttachmentItem(
			"imported_url",
			['contentType' => 'application/pdf'],
			$key,
			null,
			'jsonData'
		);
		self::$attachmentKey = self::$attachmentJSON['key'];
	}
	
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		API::userClear(self::$config['userID']);
		API::groupClear(self::$config['ownedPrivateGroupID']);
	}
	
	
	public function test_should_save_a_highlight_annotation() {
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'highlight',
			'annotationText' => 'This is highlighted text.',
			'annotationColor' => '#ff8c19',
			'annotationPageLabel' => '10',
			'annotationSortIndex' => '00015|002431|00000',
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			array("Content-Type: application/json")
		);
		$this->assert200ForObject($response);
		$json = API::getJSONFromResponse($response);
		$jsonData = $json['successful'][0]['data'];
		$this->assertEquals('annotation', (string) $jsonData['itemType']);
		$this->assertEquals('highlight', $jsonData['annotationType']);
		$this->assertEquals('This is highlighted text.', $jsonData['annotationText']);
		$this->assertEquals('#ff8c19', $jsonData['annotationColor']);
		$this->assertEquals('10', $jsonData['annotationPageLabel']);
		$this->assertEquals('00015|002431|00000', $jsonData['annotationSortIndex']);
		$position = json_decode($jsonData['annotationPosition'], true);
		$this->assertEquals(123, $position['pageIndex']);
		$this->assertSame([[314.4, 412.8, 556.2, 609.6]], $position['rects']);
	}
	
	
	public function test_should_save_a_note_annotation() {
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'note',
			'annotationComment' => 'This is a comment.',
			'annotationSortIndex' => '00015|002431|00000',
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getJSONFromResponse($response);
		$jsonData = $json['successful'][0]['data'];
		$this->assertEquals('annotation', (string) $jsonData['itemType']);
		$this->assertEquals('note', $jsonData['annotationType']);
		$this->assertEquals('This is a comment.', $jsonData['annotationComment']);
		$this->assertEquals('00015|002431|00000', $jsonData['annotationSortIndex']);
		$position = json_decode($jsonData['annotationPosition'], true);
		$this->assertEquals(123, $position['pageIndex']);
		$this->assertSame([[314.4, 412.8, 556.2, 609.6]], $position['rects']);
		$this->assertArrayNotHasKey('annotationText', $jsonData);
	}
	
	
	public function test_should_reject_empty_annotationText_for_image_annotation() {
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'image',
			'annotationText' => '',
			'annotationSortIndex' => '00015|002431|00000',
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert400ForObject($response, "'annotationText' can only be set for highlight annotations");
	}
	
	
	public function test_should_reject_non_empty_annotationText_for_image_annotation() {
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'image',
			'annotationText' => 'test',
			'annotationSortIndex' => '00015|002431|00000',
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert400ForObject($response, "'annotationText' can only be set for highlight annotations");
	}
	
	
	public function test_should_save_an_image_annotation_and_embedded_image() {
		// Create annotation
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'image',
			'annotationSortIndex' => '00015|002431|00000',
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getJSONFromResponse($response);
		$json = $json['successful'][0];
		$jsonData = $json['data'];
		$annotationKey = $json['key'];
		$this->assertEquals('annotation', $jsonData['itemType']);
		$this->assertEquals('image', $jsonData['annotationType']);
		$this->assertEquals('00015|002431|00000', $jsonData['annotationSortIndex']);
		$position = json_decode($jsonData['annotationPosition'], true);
		$this->assertEquals(123, $position['pageIndex']);
		$this->assertSame([[314.4, 412.8, 556.2, 609.6]], $position['rects']);
		$this->assertEquals(0, $json['meta']['numChildren']);
		$this->assertArrayNotHasKey('annotationText', $jsonData);
		
		// Create embedded-image annotation
		$imageKey = API::createAttachmentItem(
			'embedded_image', ['contentType' => 'image/png'], $annotationKey, $this, 'key'
		);
		
		// Check numChildren
		$json = API::getItem($annotationKey, $this, 'json');
		$this->assertEquals(1, $json['meta']['numChildren']);
		
		$json = API::getItem($imageKey, $this, 'json');
		
		$this->assertArrayNotHasKey('title', $json['data']);
		$this->assertArrayNotHasKey('url', $json['data']);
		$this->assertArrayNotHasKey('accessDate', $json['data']);
		$this->assertArrayNotHasKey('note', $json['data']);
		$this->assertArrayNotHasKey('charset', $json['data']);
		$this->assertArrayNotHasKey('path', $json['data']);
		$this->assertArrayNotHasKey('tags', $json['data']);
		$this->assertArrayNotHasKey('relations', $json['data']);
		$this->assertNull($json['data']['md5']);
		$this->assertNull($json['data']['mtime']);
		
		// Image uploading tested in FileTest
	}
	
	
	public function test_should_not_allow_changing_annotation_type() {
		// Create highlight annotation
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'highlight',
			'annotationText' => 'This is highlighted text.',
			'annotationSortIndex' => '00015|002431|00000',
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getJSONFromResponse($response)['successful'][0];
		['key' => $annotationKey, 'version' => $version] = $json;
		
		// Try to change to note annotation
		$json = [
			'version' => $version,
			'annotationType' => 'note'
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/$annotationKey",
			json_encode($json),
			["Content-Type: application/json"]
		);
		$this->assert400($response);
	}
	
	
	
	public function test_should_update_annotation_comment() {
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'highlight',
			'annotationText' => 'This is highlighted text.',
			'annotationComment' => '',
			'annotationSortIndex' => '00015|002431|00000',
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getJSONFromResponse($response)['successful'][0];
		['key' => $annotationKey, 'version' => $version] = $json;
		
		$json = [
			'key' => $annotationKey,
			'version' => $version,
			'annotationComment' => 'What a highlight!'
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/$annotationKey",
			json_encode($json),
			["Content-Type: application/json"]
		);
		$this->assert204($response);
		$json = API::getItem($annotationKey, $this, 'json');
		$this->assertEquals('What a highlight!', $json['data']['annotationComment']);
	}
	
	
	public function test_should_update_annotation_text() {
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'highlight',
			'annotationText' => 'This is highlighted text.',
			'annotationComment' => '',
			'annotationSortIndex' => '00015|002431|00000',
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getJSONFromResponse($response)['successful'][0];
		['key' => $annotationKey, 'version' => $version] = $json;
		
		$json = [
			'key' => $annotationKey,
			'version' => $version,
			'annotationText' => 'New text'
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/$annotationKey",
			json_encode($json),
			["Content-Type: application/json"]
		);
		$this->assert204($response);
		$json = API::getItem($annotationKey, $this, 'json');
		$this->assertEquals('New text', $json['data']['annotationText']);
	}
	
	
	public function test_should_clear_annotation_fields() {
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'highlight',
			'annotationText' => 'This is highlighted text.',
			'annotationComment' => 'This is a comment.',
			'annotationSortIndex' => '00015|002431|00000',
			'annotationPageLabel' => "5",
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert200ForObject($response);
		$json = API::getJSONFromResponse($response)['successful'][0];
		['key' => $annotationKey, 'version' => $version] = $json;
		
		// Try to change to note annotation
		$json = [
			'key' => $annotationKey,
			'version' => $version,
			'annotationComment' => '',
			'annotationPageLabel' => ''
		];
		$response = API::userPatch(
			self::$config['userID'],
			"items/$annotationKey",
			json_encode($json),
			["Content-Type: application/json"]
		);
		$this->assert204($response);
		$json = API::getItem($annotationKey, $this, 'json');
		$this->assertEquals('', $json['data']['annotationComment']);
		$this->assertEquals('', $json['data']['annotationPageLabel']);
	}
	
	
	public function test_should_reject_invalid_sortIndex() {
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'highlight',
			'annotationText' => '',
			'annotationSortIndex' => '0000',
			'annotationColor' => '#ff8c19',
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert400ForObject(
			$response, "Invalid sortIndex '0000'", 0
		);
	}
	
	
	public function test_should_reject_invalid_color_value() {
		$json = [
			'itemType' => 'annotation',
			'parentItem' => self::$attachmentKey,
			'annotationType' => 'highlight',
			'annotationText' => '',
			'annotationSortIndex' => '00015|002431|00000',
			'annotationColor' => 'ff8c19', // Missing '#'
			'annotationPosition' => json_encode([
				'pageIndex' => 123,
				'rects' => [
					[314.4, 412.8, 556.2, 609.6]
				]
			])
		];
		$response = API::userPost(
			self::$config['userID'],
			"items",
			json_encode([$json]),
			["Content-Type: application/json"]
		);
		$this->assert400ForObject(
			$response, "annotationColor must be a hex color (e.g., '#FF0000')", 0
		);
	}
}
