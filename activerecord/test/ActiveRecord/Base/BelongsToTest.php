<?php

class BelongsTo_User extends ActiveRecord_Base {
  public function init_class() {
    $this->set_table_name('users');
  }
}

class BelongsTo_Profile extends ActiveRecord_Base {
  public function init_class() {
    $this->set_table_name('profiles');
    $this->belongs_to('user', array('class_name'=>'BelongsTo_User'));
  }
  
  public function cached_user() { return $this->read_cached_attribute('user'); }
}

class BelongsTo_Profile_NoKeyValidation extends ActiveRecord_Base {
  public function init_class() {
    $this->set_table_name('profiles');
    $this->belongs_to('user', array('class_name'=>'BelongsTo_User', 'validate_key'=>false));
  }
}

class ActiveRecord_Base_BelongsToTest extends CriticalI_DBTestCase {
  
  public function testAssociationAccessor() {
    $profile = new BelongsTo_Profile();
    $profile = $profile->find(1);
    
    // straight access
    $user = $profile->user;
    $this->assertEquals($profile->user_id, $user->id);
    
    // test caching
    $userFixture = $this->fixture('users', 'jsmith');
    $user2 = new BelongsTo_User();
    $user2 = $user->find($profile->user_id);
    $user2->password = '*';
    $user2->save_or_fail();
    
    $user = $profile->user;
    $this->assertEquals($userFixture['password'], $user->password);
    
    // test reload
    $user = $profile->user(true);
    $this->assertEquals('*', $user->password);
    
    // test not found
    $profile->user_id = 1000;
    $this->assertEquals(null, $profile->user);
  }
  
  public function testAssociationMutator() {
    $profile = new BelongsTo_Profile();
    $profile = $profile->find(1);
    
    $user = new BelongsTo_User();
    $user = $user->find(2);
    
    // test assignment
    $profile->user = $user;
    $this->assertEquals($profile->user->id, $user->id);
    $this->assertEquals($profile->user_id, $user->id);
    
    // test saving associated object
    $user = new BelongsTo_User(array('username'=>'testing', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $profile->user = $user;
    
    $user->username = '_testing';
    $this->assertEquals('_testing', $user->username);
    $this->assertEquals('_testing', $profile->user->username);
    
    $this->assertTrue($user->new_record());
    $profile->save_or_fail();
    $this->assertFalse($user->new_record());
    $this->assertFalse(is_null($user->id));
    $this->assertEquals($user->id, $profile->user_id);
    $this->assertEquals($user->id, $profile->user->id);
    
    // test invalid type
    try {
      $profile->user = 5;
      $this->fail("Expected exception ActiveRecord_AssociationTypeMismatch was not raised.");
    } catch (ActiveRecord_AssociationTypeMismatch $ex) {
      // expected
    }
  }
  
  public function testBuildAssociation() {
    $profile = new BelongsTo_Profile();
    $profile = $profile->find(1);
    $now = date('Y-m-d H:i:s');
    
    $u = $profile->build_user(array('username'=>'testing', 'password'=>'*',
      'disabled'=>false, 'last_login'=>$now));

    $this->assertTrue($profile->user->new_record());
    $this->assertEquals('testing', $profile->user->username);
    $this->assertEquals('*', $profile->user->password);
    $this->assertFalse($profile->user->disabled);
    $this->assertEquals($now, $profile->user->last_login);
    $this->assertEquals($u, $profile->user);
  }

  public function testCreateAssociation() {
    $profile = new BelongsTo_Profile();
    $profile = $profile->find(1);
    $now = date('Y-m-d H:i:s');
    
    $u = $profile->create_user(array('username'=>'testing', 'password'=>'*',
      'disabled'=>false, 'last_login'=>$now));
    
    $this->assertFalse($profile->user->new_record());
    $this->assertEquals('testing', $profile->user->username);
    $this->assertEquals('*', $profile->user->password);
    $this->assertFalse($profile->user->disabled);
    $this->assertEquals($now, $profile->user->last_login);
    $this->assertEquals($profile->user_id, $profile->user->id);
    $this->assertEquals($u, $profile->user);
  }
  
  public function testKeyValidation() {
    $profile = new BelongsTo_Profile();
    $profile = $profile->find(1);
    
    $profile->user_id = 1;
    $this->assertTrue($profile->is_valid());
    
    $profile->user_id = 1000;
    $this->assertFalse($profile->is_valid());
    
    $profile2 = new BelongsTo_Profile_NoKeyValidation();
    $profile2 = $profile2->find(1);
    
    $profile2->user_id = 1;
    $this->assertTrue($profile2->is_valid());
    
    $profile2->user_id = 1000;
    $this->assertTrue($profile2->is_valid());
  }
  
  public function testInclude() {
    $profile = new BelongsTo_Profile();
    $profiles = $profile->find_all(array('include'=>'user'));
    
    foreach ($profiles as $prof) {
      $this->assertEquals($prof->user_id, $prof->cached_user->id);
    }

    // test no results
    $profiles = $profile->find_all(array('conditions'=>array('email=?', 'none@example.com'),
      'include'=>'user'));
    $this->assertEquals(0, count($profiles));
  }
  
}

?>