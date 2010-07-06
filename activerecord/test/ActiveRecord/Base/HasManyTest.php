<?php

class HasMany_User extends ActiveRecord_Base {
  public function init_class() {
    $this->set_table_name('users');
    $this->has_many('blog_posts', array('class_name'=>'HasMany_BlogPost', 'foreign_key'=>'user_id', 'order'=>'id'));
  }

  public function cached_blog_posts() { return $this->read_cached_attribute('blog_posts'); }
}

class HasMany_BlogPost extends ActiveRecord_Base {
  public function init_class() {
    $this->set_table_name('blog_posts');
  }
}

class HasMany_User_Ordered extends ActiveRecord_Base {
  public function init_class() {
    $this->set_table_name('users');
    $this->has_many('blog_posts', array('class_name'=>'HasMany_BlogPost', 'foreign_key'=>'user_id', 'order'=>'published_at DESC'));
  }
}


class ActiveRecord_Base_HasManyTest extends Vulture_DBTestCase {
  
  public function testAssociationAccessor() {
    $user = new HasMany_User();
    $user = $user->find(1);
    
    $posts = new HasMany_BlogPost();
    $posts = $posts->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    
    // straight access
    $count = count($posts);
    $this->assertEquals($count, $user->blog_posts->count());
    for ($i = 0; $i < $count; $i++) {
      $this->assertEquals($posts[$i]->id, $user->blog_posts[$i]->id);
    }
    
    // test caching
    $posts[0]->published = false;
    $posts[0]->save_or_fail();
    
    $this->assertTrue($user->blog_posts[0]->published);
    
    // test reload
    $posts2 = $user->blog_posts(true);
    $this->assertFalse($posts2[0]->published);
    
    // test not found
    $user = new HasMany_User(array('username'=>'testing', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $user->save_or_fail();
    $this->assertEquals(0, $user->blog_posts->count());
  }
  
  public function testCollectionCount() {
    $user = new HasMany_User();
    $user = $user->find(1);
    
    $posts = new HasMany_BlogPost();
    $posts = $posts->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    
    // count without load
    $count = count($posts);
    $this->assertEquals($count, $user->blog_posts->count());
    $this->assertEquals($count, $user->blog_posts->size());
    $this->assertEquals($count, $user->blog_posts->length());
    $this->assertEquals($count, count($user->blog_posts));
    
    // test caching
    $post = new HasMany_BlogPost(array('user_id'=>$user->id, 'published'=>1,
      'content'=>'New post.', 'published_at'=>date('Y-m-d H:i:s')));
    $post->save_or_fail();
    $this->assertEquals($count, $user->blog_posts->count());
    $this->assertEquals($count, $user->blog_posts->size());
    $this->assertEquals($count, $user->blog_posts->length());
    $this->assertEquals($count, count($user->blog_posts));
    
    // test count after load
    $user->blog_posts[0];
    $this->assertEquals($count+1, $user->blog_posts->count());
    $this->assertEquals($count+1, $user->blog_posts->size());
    $this->assertEquals($count+1, $user->blog_posts->length());
    $this->assertEquals($count+1, count($user->blog_posts));
    $user = $user->find(1);
    $user->blog_posts[0];
    $this->assertEquals($count+1, $user->blog_posts->count());
    $this->assertEquals($count+1, $user->blog_posts->size());
    $this->assertEquals($count+1, $user->blog_posts->length());
    $this->assertEquals($count+1, count($user->blog_posts));
  }
  
  public function testCollectionInsert() {
    $user = new HasMany_User();
    $user = $user->find(1);
    $count = $user->blog_posts->count();

    // append new to existing user record
    $post = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post.',
      'published_at'=>date('Y-m-d H:i:s')));
    $user->blog_posts[] = $post;
    $this->assertTrue($post->new_record());
    $this->assertEquals($user->id, $post->user_id);
    $this->assertEquals($count+1, $user->blog_posts->count());
    $this->assertEquals($post->id, $user->blog_posts[$count]->id);
    $user->save_or_fail();
    $this->assertFalse($post->new_record());
    
    // replace new in existing user record
    $newPost = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #2.',
      'published_at'=>date('Y-m-d H:i:s')));
    $user->blog_posts[$count] = $newPost;
    $post = $post->find($post->id);
    $this->assertTrue($newPost->new_record());
    $this->assertEquals($user->id, $newPost->user_id);
    $this->assertEquals(null, $post->user_id);
    $this->assertEquals($count+1, $user->blog_posts->count());
    $this->assertEquals($newPost->id, $user->blog_posts[$count]->id);
    $user->save_or_fail();
    $this->assertFalse($newPost->new_record());
    
    // append existing to existing user record
    $count = $user->blog_posts->count();
    $post = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #3.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post->save_or_fail();
    $user->blog_posts[] = $post;
    $this->assertEquals($count+1, $user->blog_posts->count());
    $this->assertEquals($post->id, $user->blog_posts[$count]->id);
    $post = $post->find($post->id);
    $this->assertEquals($user->id, $post->user_id);
    
    // replace existing in existing user record
    $newPost = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #4.',
      'published_at'=>date('Y-m-d H:i:s')));
    $newPost->save_or_fail();
    $user->blog_posts[$count] = $newPost;
    $post = $post->find($post->id);
    $this->assertEquals(null, $post->user_id);
    $this->assertEquals($count+1, $user->blog_posts->count());
    $this->assertEquals($newPost->id, $user->blog_posts[$count]->id);
    $newPost = $newPost->find($newPost->id);
    $this->assertEquals($user->id, $newPost->user_id);
    
    // append new to new user record
    $user = new HasMany_User(array('username'=>'testing#1', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $post = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #5.',
      'published_at'=>date('Y-m-d H:i:s')));
    $user->blog_posts[] = $post;
    $this->assertTrue($user->new_record());
    $this->assertTrue($post->new_record());
    $this->assertEquals(null, $post->user_id);
    $this->assertEquals(1, $user->blog_posts->count());
    $this->assertEquals($post->id, $user->blog_posts[0]->id);
    $user->save_or_fail();
    $this->assertFalse($post->new_record());
    $this->assertEquals($user->id, $post->user_id);
    
    // replace new in new user record
    $user = new HasMany_User(array('username'=>'testing#2', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $post = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #6.',
      'published_at'=>date('Y-m-d H:i:s')));
    $newPost = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #7.',
      'published_at'=>date('Y-m-d H:i:s')));
    $user->blog_posts[] = $post;
    $user->blog_posts[0] = $newPost;
    $this->assertTrue($user->new_record());
    $this->assertTrue($post->new_record());
    $this->assertTrue($newPost->new_record());
    $this->assertEquals(null, $newPost->user_id);
    $this->assertEquals(null, $post->user_id);
    $this->assertEquals(1, $user->blog_posts->count());
    $this->assertEquals($newPost->id, $user->blog_posts[0]->id);
    $user->save_or_fail();
    $this->assertFalse($newPost->new_record());
    $this->assertTrue($post->new_record());
    $this->assertEquals($user->id, $newPost->user_id);

    // append existing to new user record
    $user = new HasMany_User(array('username'=>'testing#3', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $post = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #8.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post->save_or_fail();
    $user->blog_posts[] = $post;
    $this->assertTrue($user->new_record());
    $this->assertEquals(null, $post->user_id);
    $this->assertEquals(1, $user->blog_posts->count());
    $this->assertEquals($post->id, $user->blog_posts[0]->id);
    $user->save_or_fail();
    $post = $post->find($post->id);
    $this->assertEquals($user->id, $post->user_id);
    
    // replace existing in new user record
    $user = new HasMany_User(array('username'=>'testing#4', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $post = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #9.',
      'published_at'=>date('Y-m-d H:i:s')));
    $newPost = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #10.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post->save_or_fail();
    $newPost->save_or_fail();
    $user->blog_posts[] = $post;
    $user->blog_posts[0] = $newPost;
    $this->assertTrue($user->new_record());
    $this->assertEquals(null, $newPost->user_id);
    $this->assertEquals(null, $post->user_id);
    $this->assertEquals(1, $user->blog_posts->count());
    $this->assertEquals($newPost->id, $user->blog_posts[0]->id);
    $user->save_or_fail();
    $post = $post->find($post->id);
    $newPost = $newPost->find($newPost->id);
    $this->assertEquals($user->id, $newPost->user_id);
    $this->assertEquals(null, $post->user_id);
  }
  
  public function testCollectionIteration() {
    $user = new HasMany_User();
    $user = $user->find(1);
    
    $posts = new HasMany_BlogPost();
    $posts = $posts->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    
    foreach ($user->blog_posts as $idx=>$post) {
      $this->assertEquals($posts[$idx]->id, $post->id);
    }
  }
  
  public function testCollectionIndexAccess() {
    $user = new HasMany_User();
    $user = $user->find(1);
    
    $posts = new HasMany_BlogPost();
    $posts = $posts->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    
    $count = count($posts);
    for ($i = 0; $i < $count; $i++) {
      $this->assertEquals($posts[$i]->id, $user->blog_posts[$i]->id);
    }
    $this->assertEquals(null, $user->blog_posts[$count]);
  }
  
  public function testCollectionClear() {
    $user = new HasMany_User();
    $user = $user->find(1);
    
    $user->blog_posts->clear();
    
    $posts = new HasMany_BlogPost();
    $posts = $posts->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    
    $this->assertEquals(0, $user->blog_posts->count());
    $this->assertEquals(0, count($posts));
  }
  
  public function testCollectionEmpty() {
    $user = new HasMany_User();
    $user = $user->find(1);
    
    $user2 = new HasMany_User(array('username'=>'testing#1', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $user2->save_or_fail();
    
    $this->assertFalse($user->blog_posts->is_empty());
    $this->assertTrue($user2->blog_posts->is_empty());
  }
  
  public function testCollectionFind() {
    $user = new HasMany_User();
    $user = $user->find(1);
    
    // by id
    $this->assertEquals($user->blog_posts[0]->id, $user->blog_posts->find($user->blog_posts[0]->id)->id);
    $this->assertEquals(2, count($user->blog_posts->find($user->blog_posts[0]->id, $user->blog_posts[1]->id)));
    $this->assertEquals(2, count($user->blog_posts->find(array($user->blog_posts[0]->id, $user->blog_posts[1]->id))));
    try {
      $user->blog_posts->find(-1);
      $this->fail('Did not raise ActiveRecord_NotFoundError for collection->find() with a non-existent id.');
    } catch (ActiveRecord_NotFoundError $err) {
      // expected
    }
    try {
      $user->blog_posts->find($user->blog_posts[0]->id, -1);
      $this->fail('Did not raise ActiveRecord_NotFoundError for collection->find() with a non-existent id.');
    } catch (ActiveRecord_NotFoundError $err) {
      // expected
    }
    
    // first
    $this->assertEquals('Post #1', $user->blog_posts->find_first(array('conditions'=>"content='Post #1'"))->content);
    $this->assertEquals(null, $user->blog_posts->find_first(array('conditions'=>"content='Post #3'")));
    
    // all
    $user2 = $user->find(2);
    $matches = $user->blog_posts->find_all(array('conditions'=>"content='Post #1'"));
    $this->assertEquals('Post #1', $matches[0]->content);
    $this->assertEquals(2, count($user2->blog_posts->find_all(array('conditions'=>"published=1"))));
    $this->assertEquals(0, count($user->blog_posts->find_first(array('conditions'=>"content='Post #3'"))));
  }
  
  public function testCollectionExists() {
    $user = new HasMany_User();
    $user = $user->find(1);
    
    $this->assertTrue($user->blog_posts->exists($user->blog_posts[0]->id));
    $this->assertFalse($user->blog_posts->exists(-1));
    $this->assertTrue($user->blog_posts->exists(array('conditions'=>"content='Post #1'")));
    $this->assertFalse($user->blog_posts->exists(array('conditions'=>"content='Post #3'")));
  }
  
  public function testAssociationMutatorMany() {
    // replace existing collection on an existing record
    $user = new HasMany_User();
    $user = $user->find(1);
    $oldPost = $user->blog_posts[0];
    $post1 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post1->save_or_fail();
    $post2 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #2.',
      'published_at'=>date('Y-m-d H:i:s')));
    $posts = array($oldPost, $post1, $post2);
    $user->blog_posts = $posts;
    $this->assertEquals(3, $user->blog_posts->count());
    $this->assertEquals($oldPost->id, $user->blog_posts[0]->id);
    $this->assertEquals($post1->id, $user->blog_posts[1]->id);
    $this->assertEquals($post2->id, $user->blog_posts[2]->id);
    $this->assertTrue($user->blog_posts[2]->new_record());
    $dbPosts = $oldPost->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    $this->assertEquals(2, count($dbPosts));
    $this->assertEquals($oldPost->id, $dbPosts[0]->id);
    $this->assertEquals($post1->id, $dbPosts[1]->id);
    $user->save_or_fail();
    $this->assertFalse($user->blog_posts[2]->new_record());
    $dbPosts = $oldPost->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    $this->assertEquals(3, count($dbPosts));

    // new collection for an existing record
    $user = new HasMany_User(array('username'=>'testing#1', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $user->save_or_fail();
    $jdoe_post2 = $this->fixture('blog_posts', 'jdoe_post2');
    $oldPost = $post1->find($jdoe_post2['id']);
    $post1 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post1->save_or_fail();
    $post2 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #2.',
      'published_at'=>date('Y-m-d H:i:s')));
    $posts = array($oldPost, $post1, $post2);
    $user->blog_posts = $posts;
    $this->assertEquals(3, $user->blog_posts->count());
    $this->assertEquals($oldPost->id, $user->blog_posts[0]->id);
    $this->assertEquals($post1->id, $user->blog_posts[1]->id);
    $this->assertEquals($post2->id, $user->blog_posts[2]->id);
    $this->assertTrue($user->blog_posts[2]->new_record());
    $dbPosts = $oldPost->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    $this->assertEquals(2, count($dbPosts));
    $this->assertEquals($oldPost->id, $dbPosts[0]->id);
    $this->assertEquals($post1->id, $dbPosts[1]->id);
    $user->save_or_fail();
    $this->assertFalse($user->blog_posts[2]->new_record());
    $dbPosts = $oldPost->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    $this->assertEquals(3, count($dbPosts));
    
    // replace collection on a new record
    $user = new HasMany_User(array('username'=>'testing#2', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $jdoe_post2 = $this->fixture('blog_posts', 'jdoe_post2');
    $oldPost = $post1->find($jdoe_post2['id']);
    $user->blog_posts[] = $oldPost;
    $post1 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post1->save_or_fail();
    $post2 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #2.',
      'published_at'=>date('Y-m-d H:i:s')));
    $posts = array($oldPost, $post1, $post2);
    $user->blog_posts = $posts;
    $this->assertEquals(3, $user->blog_posts->count());
    $this->assertEquals($oldPost->id, $user->blog_posts[0]->id);
    $this->assertEquals($post1->id, $user->blog_posts[1]->id);
    $this->assertEquals($post2->id, $user->blog_posts[2]->id);
    $this->assertTrue($user->blog_posts[2]->new_record());
    $user->save_or_fail();
    $this->assertFalse($user->blog_posts[2]->new_record());
    $dbPosts = $oldPost->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    $this->assertEquals(3, count($dbPosts));
    
    // new collection on a new record
    $user = new HasMany_User(array('username'=>'testing#3', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $jdoe_post2 = $this->fixture('blog_posts', 'jdoe_post2');
    $oldPost = $post1->find($jdoe_post2['id']);
    $post1 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post1->save_or_fail();
    $post2 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #2.',
      'published_at'=>date('Y-m-d H:i:s')));
    $posts = array($oldPost, $post1, $post2);
    $user->blog_posts = $posts;
    $this->assertEquals(3, $user->blog_posts->count());
    $this->assertEquals($oldPost->id, $user->blog_posts[0]->id);
    $this->assertEquals($post1->id, $user->blog_posts[1]->id);
    $this->assertEquals($post2->id, $user->blog_posts[2]->id);
    $this->assertTrue($user->blog_posts[2]->new_record());
    $user->save_or_fail();
    $this->assertFalse($user->blog_posts[2]->new_record());
    $dbPosts = $oldPost->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    $this->assertEquals(3, count($dbPosts));
  }
  
  public function testAssociationIdAccess() {
    // collection ids
    $user = new HasMany_User();
    $user = $user->find(1);
    $jdoe_post1 = $this->fixture('blog_posts', 'jdoe_post1');
    $jdoe_post2 = $this->fixture('blog_posts', 'jdoe_post2');
    $this->assertEquals(array($jdoe_post1['id'], $jdoe_post2['id']), $user->blog_post_ids);

    // empty collection
    $user2 = new HasMany_User(array('username'=>'testing#1', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $user2->save_or_fail();
    $this->assertEquals(array(), $user2->blog_post_ids);
    
    // collection with unsaved records
    $post = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>date('Y-m-d H:i:s')));
    $user->blog_posts[] = $post;
    $this->assertEquals(array($jdoe_post1['id'], $jdoe_post2['id']), $user->blog_post_ids);
  }
  
  public function testAssocationIdMutation() {
    // replace existing collection on an existing record
    $user = new HasMany_User();
    $user = $user->find(1);
    $oldPost = $user->blog_posts[0];
    $post1 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post1->save_or_fail();
    $post2 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #2.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post2->save_or_fail();
    $postIds = array($oldPost->id, $post1->id, $post2->id);
    $user->blog_post_ids = $postIds;
    $this->assertEquals(3, $user->blog_posts->count());
    $this->assertEquals($oldPost->id, $user->blog_posts[0]->id);
    $this->assertEquals($post1->id, $user->blog_posts[1]->id);
    $this->assertEquals($post2->id, $user->blog_posts[2]->id);
    $dbPosts = $oldPost->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    $this->assertEquals(3, count($dbPosts));
    $this->assertEquals($oldPost->id, $dbPosts[0]->id);
    $this->assertEquals($post1->id, $dbPosts[1]->id);
    $this->assertEquals($post2->id, $dbPosts[2]->id);

    // new collection for an existing record
    $user = new HasMany_User(array('username'=>'testing#1', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $user->save_or_fail();
    $jdoe_post2 = $this->fixture('blog_posts', 'jdoe_post2');
    $oldPost = $post1->find($jdoe_post2['id']);
    $post1 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post1->save_or_fail();
    $post2 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #2.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post2->save_or_fail();
    $postIds = array($oldPost->id, $post1->id, $post2->id);
    $user->blog_post_ids = $postIds;
    $this->assertEquals(3, $user->blog_posts->count());
    $this->assertEquals($oldPost->id, $user->blog_posts[0]->id);
    $this->assertEquals($post1->id, $user->blog_posts[1]->id);
    $this->assertEquals($post2->id, $user->blog_posts[2]->id);
    $dbPosts = $oldPost->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    $this->assertEquals(3, count($dbPosts));
    $this->assertEquals($oldPost->id, $dbPosts[0]->id);
    $this->assertEquals($post1->id, $dbPosts[1]->id);
    $this->assertEquals($post2->id, $dbPosts[2]->id);
    
    // replace collection on a new record
    $user = new HasMany_User(array('username'=>'testing#2', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $jdoe_post2 = $this->fixture('blog_posts', 'jdoe_post2');
    $oldPost = $post1->find($jdoe_post2['id']);
    $user->blog_posts[] = $oldPost;
    $post1 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post1->save_or_fail();
    $post2 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #2.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post2->save_or_fail();
    $posts = array($oldPost->id, $post1->id, $post2->id);
    $user->blog_post_ids = $posts;
    $this->assertEquals(3, $user->blog_posts->count());
    $this->assertEquals($oldPost->id, $user->blog_posts[0]->id);
    $this->assertEquals($post1->id, $user->blog_posts[1]->id);
    $this->assertEquals($post2->id, $user->blog_posts[2]->id);
    $user->save_or_fail();
    $dbPosts = $oldPost->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    $this->assertEquals(3, count($dbPosts));
    
    // new collection on a new record
    $user = new HasMany_User(array('username'=>'testing#3', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $jdoe_post2 = $this->fixture('blog_posts', 'jdoe_post2');
    $oldPost = $post1->find($jdoe_post2['id']);
    $post1 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post1->save_or_fail();
    $post2 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #2.',
      'published_at'=>date('Y-m-d H:i:s')));
    $post2->save_or_fail();
    $posts = array($oldPost->id, $post1->id, $post2->id);
    $user->blog_post_ids = $posts;
    $this->assertEquals(3, $user->blog_posts->count());
    $this->assertEquals($oldPost->id, $user->blog_posts[0]->id);
    $this->assertEquals($post1->id, $user->blog_posts[1]->id);
    $this->assertEquals($post2->id, $user->blog_posts[2]->id);
    $user->save_or_fail();
    $dbPosts = $oldPost->find_all(array('conditions'=>array('user_id=?', $user->id), 'order'=>'id'));
    $this->assertEquals(3, count($dbPosts));
  }
  
  public function testCollectionDelete() {
    // remove existing from existing
    $user = new HasMany_User();
    $user = $user->find(1);
    $post = $user->blog_posts[0];
    $user->blog_posts->delete($post);
    $this->assertEquals(null, $post->user_id);
    $this->assertEquals(1, $user->blog_posts->count());
    $this->assertFalse($user->blog_posts[0]->id === $post->id);
    $dbPosts = $post->find_all(array('conditions'=>array('user_id=?', $user->id)));
    $this->assertEquals(1, count($dbPosts));
    $this->assertFalse($dbPosts[0]->id === $post->id);

    // remove new from existing
    $user = new HasMany_User();
    $user = $user->find(1);
    $post1 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>date('Y-m-d H:i:s')));
    $user->blog_posts[] = $post1;
    $user->blog_posts->delete($post1);
    $this->assertEquals(null, $post1->user_id);
    $this->assertEquals(1, $user->blog_posts->count());
    $this->assertFalse(is_null($user->blog_posts[0]->id));
    $dbPosts = $post->find_all(array('conditions'=>array('user_id=?', $user->id)));
    $this->assertEquals(1, count($dbPosts));

    // remove existing from new
    $user = new HasMany_User(array('username'=>'testing#1', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $user->blog_posts[] = $post;
    $user->blog_posts->delete($post);
    $this->assertEquals(null, $post->user_id);
    $this->assertEquals(0, $user->blog_posts->count());

    // remove new from new
    $post2 = new HasMany_BlogPost(array('published'=>1, 'content'=>'New post #2.',
      'published_at'=>date('Y-m-d H:i:s')));
    $user->blog_posts[] = $post2;
    $user->blog_posts->delete($post2);
    $this->assertEquals(null, $post2->user_id);
    $this->assertEquals(0, $user->blog_posts->count());
  }
  
  public function testCollectionBuild() {
    $now = date('Y-m-d H:i:s');
    
    // existing record
    $user = new HasMany_User();
    $user = $user->find(1);
    $post = $user->blog_posts->build(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>$now));
    $this->assertEquals(3, $user->blog_posts->count());
    $this->assertTrue($post->new_record());
    $this->assertEquals(true, $post->published);
    $this->assertEquals('New post #1.', $post->content);
    $this->assertEquals($now, $post->published_at);
    $this->assertEquals($user->id, $post->user_id);
    $user->save_or_fail();
    $this->assertFalse($post->new_record());
    $this->assertEquals($post->id, $user->blog_posts[2]->id);
    
    // new record
    $user = new HasMany_User(array('username'=>'testing#1', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $post = $user->blog_posts->build(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>$now));
    $this->assertEquals(1, $user->blog_posts->count());
    $this->assertTrue($post->new_record());
    $this->assertEquals(true, $post->published);
    $this->assertEquals('New post #1.', $post->content);
    $this->assertEquals($now, $post->published_at);
    $user->save_or_fail();
    $this->assertFalse($post->new_record());
    $this->assertEquals($user->id, $post->user_id);
    $this->assertEquals($post->id, $user->blog_posts[0]->id);
  }
  
  public function testCollectionCreate() {
    $now = date('Y-m-d H:i:s');
    
    // existing record
    $user = new HasMany_User();
    $user = $user->find(1);
    $post = $user->blog_posts->create(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>$now));
    $this->assertEquals(3, $user->blog_posts->count());
    $this->assertFalse($post->new_record());
    $this->assertEquals(true, $post->published);
    $this->assertEquals('New post #1.', $post->content);
    $this->assertEquals($now, $post->published_at);
    $this->assertEquals($user->id, $post->user_id);
    $this->assertEquals($post->id, $user->blog_posts[2]->id);
    
    // new record
    $user = new HasMany_User(array('username'=>'testing#1', 'password'=>'*',
      'disabled'=>false, 'last_login'=>date('Y-m-d H:i:s')));
    $post = $user->blog_posts->create(array('published'=>1, 'content'=>'New post #1.',
      'published_at'=>$now));
    $this->assertEquals(1, $user->blog_posts->count());
    $this->assertFalse($post->new_record());
    $this->assertEquals(true, $post->published);
    $this->assertEquals('New post #1.', $post->content);
    $this->assertEquals($now, $post->published_at);
    $user->save_or_fail();
    $this->assertEquals($user->id, $post->user_id);
    $this->assertEquals($post->id, $user->blog_posts[0]->id);
  }
  
  public function testOrder() {
    $userIds = new HasMany_User();
    $userIds = $userIds->find(1);

    $userDates = new HasMany_User_Ordered();
    $userDates = $userDates->find(1);

    $jdoe_post1 = $this->fixture('blog_posts', 'jdoe_post1');
    $jdoe_post2 = $this->fixture('blog_posts', 'jdoe_post2');
    
    $this->assertEquals($jdoe_post1['id'], $userIds->blog_posts[0]->id);
    $this->assertEquals($jdoe_post2['id'], $userIds->blog_posts[1]->id);

    $this->assertEquals($jdoe_post2['id'], $userDates->blog_posts[0]->id);
    $this->assertEquals($jdoe_post1['id'], $userDates->blog_posts[1]->id);
  }
  
  public function testInclude() {
    $user = new HasMany_User();
    $users = $user->find_all(array('include'=>'blog_posts'));
    $post = new HasMany_BlogPost();
    
    foreach ($users as $user) {
      $count = $post->count(array('conditions'=>array('user_id=?', $user->id)));
      $this->assertEquals($count, $user->cached_blog_posts->count());
      foreach ($user->cached_blog_posts as $item) {
        $this->assertEquals($user->id, $item->user_id);
      }
    }
  }

}

?>