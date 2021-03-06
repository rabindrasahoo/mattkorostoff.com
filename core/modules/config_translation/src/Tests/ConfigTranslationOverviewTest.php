<?php

/**
 * @file
 * Contains \Drupal\config_translation\Tests\ConfigTranslationOverviewTest.
 */

namespace Drupal\config_translation\Tests;

use Drupal\Component\Utility\String;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Translate settings and entities to various languages.
 *
 * @group config_translation
 */
class ConfigTranslationOverviewTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contact', 'config_translation', 'views', 'views_ui', 'contextual', 'config_test', 'config_translation_test');

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $langcodes = array('fr', 'ta');

  /**
   * String translation storage object.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  protected function setUp() {
    parent::setUp();
    $permissions = array(
      'translate configuration',
      'administer languages',
      'administer site configuration',
      'administer contact forms',
      'access site-wide contact form',
      'access contextual links',
      'administer views',
    );
    // Create and login user.
    $this->drupalLogin($this->drupalCreateUser($permissions));

    // Add languages.
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->localeStorage = $this->container->get('locale.storage');
  }

  /**
   * Tests the config translation mapper page.
   */
  public function testMapperListPage() {
    $this->drupalGet('admin/config/regional/config-translation');
    $this->assertLinkByHref('admin/config/regional/config-translation/config_test');
    $this->assertLinkByHref('admin/config/people/accounts/translate');

    $labels = array(
      '&$nxd~i0',
      'some "label" with quotes',
      $this->randomString(),
    );

    foreach ($labels as $label) {
      $test_entity = entity_create('config_test', array(
        'id' => $this->randomMachineName(),
        'label' => $label,
      ));
      $test_entity->save();

      $base_url = 'admin/structure/config_test/manage/' . $test_entity->id();
      $this->drupalGet('admin/config/regional/config-translation/config_test');
      $this->assertLinkByHref($base_url . '/translate');
      $this->assertText(String::checkPlain($test_entity->label()));

      $entity_type = \Drupal::entityManager()->getDefinition($test_entity->getEntityTypeId());
      $this->drupalGet($base_url . '/translate');

      $title = t('!label !entity_type', array('!label' => $test_entity->label(), '!entity_type' => $entity_type->getLowercaseLabel()));
      $title = t('Translations for %label', array('%label' => $title));
      $this->assertRaw($title);
      $this->assertRaw('<th>' . t('Language') . '</th>');

      $this->drupalGet($base_url);
      $this->assertLink(t('Translate @title', array('@title' => $entity_type->getLowercaseLabel())));
    }
  }

  /**
   * Tests availability of hidden entities in the translation overview.
   */
  public function testHiddenEntities() {
    // Hidden languages are only available to translate through the
    // configuration translation listings.
    $this->drupalGet('admin/config/regional/config-translation/configurable_language');
    $this->assertText('Not applicable');
    $this->assertLinkByHref('admin/config/regional/language/edit/zxx/translate');
    $this->assertText('Not specified');
    $this->assertLinkByHref('admin/config/regional/language/edit/und/translate');

    // Hidden date formats are only available to translate through the
    // configuration translation listings. Test a couple of them.
    $this->drupalGet('admin/config/regional/config-translation/date_format');
    $this->assertText('HTML Date');
    $this->assertLinkByHref('admin/config/regional/date-time/formats/manage/html_date/translate');
    $this->assertText('HTML Year');
    $this->assertLinkByHref('admin/config/regional/date-time/formats/manage/html_year/translate');
  }

}
