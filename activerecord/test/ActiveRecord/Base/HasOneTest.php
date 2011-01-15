<?php

class HasOne_User extends ActiveRecord_Base {
  public function init_class() {
    $this->set_table_name('users');
    $this->has_one('profile', array('class_name'=>'HasOne_Profile', 'foreign_key'=>'user_id'));
  }

  public function cached_profile() { return $this->read_cached_attribute('profile'); }
}

class HasOne_Profile extends ActiveRecord_Base {
  public function init_class() {
    $this->set_table_name('profiles');
  }
}


class ActiveRecord_Base_HasOneTest extends CriticalI_DBTestCase {
  
  public function testAssociationAccessor() {
    $user = new HasOne_User();
    $user = $user->find(1);
    
    // straight access
    $profile = $user->profile;
    $this->assertEquals($user->id, $profile->user_id);
    
    // test caching
    $profile2 = $profile->find($profile->id);
    $profile2->first_name = 'J';
    $profile2->save_or_fail();
    
    $profile = $user->profile;
    $this->assertEquals('Jane', $profile->first_name);
    
    // test reload
    $profile = $user->profile(true);
    $this->assertEquals('J', $profile->first_name);
    
    // test not found
    $user = new HasOne_User(array('username'=>'testing', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $user->save_or_fail();
    $this->assertEquals(null, $user->profile);
  }
  
  public function testAssociationMutator() {
    $user = new HasOne_User();
    $user = $user->find(1);
    
    $profile = new HasOne_Profile();
    $profile = $profile->find(2);
    
    // test assignment
    $user->profile = $profile;
    $this->assertEquals($user->id, $user->profile->user_id);
    $this->assertEquals($user->id, $profile->user_id);
    
    $oldProfile = $profile->find(1);
    $newProfile = $profile->find(2);
    $this->assertEquals(null, $oldProfile->user_id);
    $this->assertEquals($user->id, $newProfile->user_id);
    
    // test assigning a new record
    $newProfile = new HasOne_Profile(array('first_name'=>'John', 'last_name'=>'Doe',
      'email'=>'john.doe@example.com'));
    $user->profile = $newProfile;
    $this->assertEquals(null, $profile->find(2)->user_id);
    $this->assertEquals($user->id, $newProfile->user_id);
    $this->assertTrue($newProfile->new_record());
    $user->save();
    $this->assertFalse($newProfile->new_record());
    
    // test assigning to a new record
    $user = new HasOne_User(array('username'=>'testing', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $newProfile = $profile->find(1);
    $user->profile = $newProfile;
    $this->assertEquals(null, $newProfile->user_id);
    $this->assertTrue($user->new_record());
    $user->save();
    $this->assertFalse($user->new_record());
    $this->assertEquals($user->id, $newProfile->user_id);
    
    $user = new HasOne_User(array('username'=>'testing#2', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $newProfile = new HasOne_Profile(array('first_name'=>'Jill', 'last_name'=>'Doe',
      'email'=>'jill.doe@example.com'));
    $user->profile = $newProfile;
    $this->assertEquals(null, $newProfile->user_id);
    $this->assertTrue($user->new_record());
    $this->assertTrue($newProfile->new_record());
    $user->save();
    $this->assertFalse($user->new_record());
    $this->assertFalse($newProfile->new_record());
    $this->assertEquals($user->id, $newProfile->user_id);

    // test invalid type
    try {
      $user->profile = 5;
      $this->fail("Expected exception ActiveRecord_AssociationTypeMismatch was not raised.");
    } catch (ActiveRecord_AssociationTypeMismatch $ex) {
      // expected
    }
  }

  public function testBuildAssociation() {
    // existing user record
    $user = new HasOne_User();
    $user = $user->find(1);
    
    $profile = $user->build_profile(array('first_name'=>'John', 'last_name'=>'Doe',
      'email'=>'john.doe@example.com'));

    $this->assertTrue($user->profile->new_record());
    $this->assertEquals($user->id, $user->profile->user_id);
    $this->assertEquals('John', $user->profile->first_name);
    $this->assertEquals('Doe', $user->profile->last_name);
    $this->assertEquals('john.doe@example.com', $user->profile->email);
    $this->assertEquals($profile, $user->profile);
    $user->save();
    $this->assertFalse($user->profile->new_record());

    // new user record
    $user = new HasOne_User(array('username'=>'testing', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));

    $profile = $user->build_profile(array('first_name'=>'Jill', 'last_name'=>'Doe',
      'email'=>'jill.doe@example.com'));

    $this->assertTrue($user->new_record());
    $this->assertTrue($user->profile->new_record());
    $this->assertEquals(null, $user->profile->user_id);
    $this->assertEquals('Jill', $user->profile->first_name);
    $this->assertEquals('Doe', $user->profile->last_name);
    $this->assertEquals('jill.doe@example.com', $user->profile->email);
    $this->assertEquals($profile, $user->profile);
    $user->save();
    $this->assertFalse($user->new_record());
    $this->assertFalse($user->profile->new_record());
    $this->assertEquals($user->id, $user->profile->user_id);
  }

  public function testCreateAssociation() {
    // existing user record
    $user = new HasOne_User();
    $user = $user->find(1);
    
    $profile = $user->create_profile(array('first_name'=>'John', 'last_name'=>'Doe',
      'email'=>'john.doe@example.com'));

    $this->assertFalse($user->profile->new_record());
    $this->assertEquals($user->id, $user->profile->user_id);
    $this->assertEquals('John', $user->profile->first_name);
    $this->assertEquals('Doe', $user->profile->last_name);
    $this->assertEquals('john.doe@example.com', $user->profile->email);
    $this->assertEquals($profile, $user->profile);

    // new user record
    $user = new HasOne_User(array('username'=>'testing', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));

    $profile = $user->create_profile(array('first_name'=>'Jill', 'last_name'=>'Doe',
      'email'=>'jill.doe@example.com'));

    $this->assertTrue($user->new_record());
    $this->assertFalse($user->profile->new_record());
    $this->assertEquals(null, $user->profile->user_id);
    $this->assertEquals('Jill', $user->profile->first_name);
    $this->assertEquals('Doe', $user->profile->last_name);
    $this->assertEquals('jill.doe@example.com', $user->profile->email);
    $this->assertEquals($profile, $user->profile);
    $user->save();
    $this->assertFalse($user->new_record());
    $this->assertFalse($user->profile->new_record());
    $this->assertEquals($user->id, $user->profile->user_id);
  }

  public function testInclude() {
    $user = new HasOne_User();
    $users = $user->find_all(array('include'=>'profile'));
    
    foreach ($users as $user) {
      $this->assertEquals($user->id, $user->cached_profile->user_id);
    }

    // test no results
    $users = $user->find_all(array('conditions'=>array('username=?', 'testing#9'),
      'include'=>'profile'));
    $this->assertEquals(0, count($users));
  }

}

?>