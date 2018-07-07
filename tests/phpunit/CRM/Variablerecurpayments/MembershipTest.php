<?php

use CRM_Variablerecurpayments_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Variablerecurpayments_MembershipTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use \Civi\Test\Api3TestTrait;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function addRequires() {
    require_once(__DIR__ . '/../../../../CRM/Variablerecurpayments/Utils.php');
    require_once(__DIR__ . '/../../../../CRM/Variablerecurpayments/Membership.php');
  }

  /**
   * This class mocks custom fields without actually adding them
   */
  public function addStatics() {
    $className = 'CRM_Variablerecurpayments_Utils';
    $customFieldId = 1;
    Civi::$statics[$className]['enable_monthly_amounts']['string'] = 'custom_' . $customFieldId;
    $customFieldId++;
    for ($count = 1; $count < 13; $count++) {
      Civi::$statics[$className]['month_' . $count]['string'] = 'custom_' . $customFieldId;
      $customFieldId++;
    }
    Civi::$statics[$className]['pro_rata']['string'] = 'custom_' . $customFieldId;
    Civi::$statics[$className]['pro_rata_start_month']['string'] = 'custom_' . $customFieldId;
  }

  /*
   * NOT IMPLEMENTED (we need to add a membership type first so API calls work*/
  public function testProRata() {
    $option = array (
      'id' => '26',
      'price_field_id' => '13',
      'name' => 'General',
      'label' => 'General',
      'description' => 'Regular annual membership.',
      'amount' => '100.000000000',
      'weight' => '1',
      'membership_type_id' => '1',
      'membership_num_terms' => '1',
      'is_default' => '1',
      'is_active' => '1',
      'financial_type_id' => '2',
      'non_deductible_amount' => '0.00',
      'visibility_id' => '1',
      'tax_rate' => '20.00000000',
      'tax_amount' => 20.0,
    );

    CRM_Variablerecurpayments_Membership::proRata($option);
  }

  /**
   * Test the getMonthlyAmount function
   *
   * @dataProvider getMonthlyAmountsDataSet
   *
   * @param array $dataSet
   *
   * @throws \Exception
   */
  public function testGetMonthlyAmount($data, $membershipTypeDetails) {
    $this->addStatics();
    timecop_travel($data['timestamp']);
    try {
      $monthlyAmount = CRM_Variablerecurpayments_Membership::getMonthlyAmount($membershipTypeDetails, $data['monthModifier']);
      $this->assertEquals($data['expected'], $monthlyAmount);
    }
    catch (CRM_Core_Exception $e) {
      $msg = $e->getMessage();
      if (($msg !== 'Month modifier cannot be greater than 12') && ($msg !== 'Month modifier cannot be less than -12')) {
        Throw $e;
      }
    }
  }

  /**
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function getMonthlyAmountsDataSet() {
    $this->addRequires();
    $this->addStatics();

    $membershipTypeData = array(
      'id' => '4',
      'domain_id' => '1',
      'name' => 'Monthly variable amounts',
      'member_of_contact_id' => '150',
      'financial_type_id' => '2',
      'duration_unit' => 'month',
      'duration_interval' => '1',
      'period_type' => 'rolling',
      'visibility' => 'Public',
      'weight' => '4',
      'auto_renew' => '2',
      'is_active' => '1',
      'contribution_type_id' => '2',
    );

    $membershipTypeData[CRM_Variablerecurpayments_Utils::getField('enable_monthly_amounts')] = 1;
    for ($count = 1; $count < 13; $count++) {
      $membershipTypeData[CRM_Variablerecurpayments_Utils::getField('month_' . $count)] = ($count * 10) . '.00';
    }

    // Test each of the 12 months
    for ($count = 1; $count < 13; $count++) {
      $data = array(
        'timestamp' => mktime(12, 0, 0, $count, 1, 2008),
        'expected' => ($count * 10) . '.00',
        'monthModifier' => 0,
      );
      $result[] = array($data, $membershipTypeData);
    }

    // Test month modifiers
    $data = array(
      'timestamp' => mktime(12, 0, 0, 1, 1, 2008),
      'expected' => '20.00',
      'monthModifier' => 1,
    );
    $result[] = array($data, $membershipTypeData);
    $data = array(
      'timestamp' => mktime(12, 0, 0, 1, 1, 2008),
      'expected' => '120.00',
      'monthModifier' => -1,
    );
    $result[] = array($data, $membershipTypeData);
    // These two should throw exceptions
    $data = array(
      'timestamp' => mktime(12, 0, 0, 1, 1, 2008),
      'expected' => '120.00',
      'monthModifier' => -13,
    );
    $result[] = array($data, $membershipTypeData);
    $data = array(
      'timestamp' => mktime(12, 0, 0, 1, 1, 2008),
      'expected' => '20.00',
      'monthModifier' => 13,
    );
    $result[] = array($data, $membershipTypeData);

    return $result;
  }



}
