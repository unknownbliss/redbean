<?php
/**
 * RedUNIT_Base_Association 
 * @file 			RedUNIT/Base/Association.php
 * @description		Tests Association API (N:N associations)
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Association extends RedUNIT_Base {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
	
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		
		$rb = $redbean;
		$testA = $rb->dispense( 'testA' );
		$testB = $rb->dispense( 'testB' );
		$a = new RedBean_AssociationManager( $toolbox );
		try {
			$a->related( $testA, "testB" );
			pass();
		}catch(Exception $e) {
			fail();
		}
		
		$user = $redbean->dispense("user");
		$user->name = "John";
		$redbean->store( $user );
		$page = $redbean->dispense("page");
		$page->name = "John's page";
		$redbean->store($page);
		$page2 = $redbean->dispense("page");
		$page2->name = "John's second page";
		$redbean->store($page2);
		$a = new RedBean_AssociationManager( $toolbox );
		$a->associate($page, $user);
		asrt(count($a->related($user, "page" )),1);
		$a->associate($user,$page2);
		asrt(count($a->related($user, "page" )),2);
		//can we fetch the assoc ids themselves?
		$pageKeys = $a->related($user, "page" );
		$pages = $redbean->batch("page",$pageKeys);
		$links = $redbean->batch("page_user",$a->related($user,"page",true));
		asrt(count($links),2);
		//confirm that the link beans are ok.
		$link = array_pop($links);
		asrt(isset($link->page_id),true);
		asrt(isset($link->user_id),true);
		asrt(isset($link->id),true);
		$link = array_pop($links);
		asrt(isset($link->page_id),true);
		asrt(isset($link->user_id),true);
		asrt(isset($link->id),true);
		
		$a->unassociate($page, $user);
		asrt(count($a->related($user, "page" )),1);
		$a->clearRelations($user, "page");
		asrt(count($a->related($user, "page" )),0);
		$user2 = $redbean->dispense("user");
		$user2->name = "Second User";
		
		
		
		
		set1toNAssoc($a,$user2, $page);
		set1toNAssoc($a,$user, $page);
		asrt(count($a->related($user2, "page" )),0);
		asrt(count($a->related($user, "page" )),1);
		set1toNAssoc($a,$user, $page2);
		asrt(count($a->related($user, "page" )),2);
		$pages = ($redbean->batch("page", $a->related($user, "page" )));
		asrt(count($pages),2);
		$apage = array_shift($pages);
		asrt(($apage->name=="John's page" || $apage->name=="John's second page"),true);
		$apage = array_shift($pages);
		asrt(($apage->name=="John's page" || $apage->name=="John's second page"),true);
		//test save on the fly
		$page = $redbean->dispense("page");
		$page2 = $redbean->dispense("page");
		$page->name="idless page 1";
		$page2->name="idless page 1";
		$a->associate($page, $page2);
		asrt(($page->id>0),true);
		asrt(($page2->id>0),true);
		$idpage = $page->id;
		$idpage2 = $page2->id;
		
		
		$page = $redbean->dispense("page");
		$page->name = "test page";
		$id = $redbean->store($page);
		$user = $redbean->dispense("user");
		$a->unassociate($user,$page);
		pass(); //no error
		$a->unassociate($page,$user);
		pass(); //no error
		$a->clearRelations($page, "user");
		pass(); //no error
		$a->clearRelations($user, "page");
		pass(); //no error
		$a->associate($user,$page);
		pass();
		asrt(count($a->related( $user, "page")),1);
		asrt(count($a->related( $page, "user")),1);
		$a->clearRelations($user, "page");
		pass(); //no error
		asrt(count($a->related( $user, "page")),0);
		asrt(count($a->related( $page, "user")),0);
		$page = $redbean->load("page",$id);
		pass();
		asrt($page->name,"test page");
		
		
		testpack("unrelated");
		R::nuke();
		$painter = R::dispense('person');
		$painter->job = 'painter';
		$accountant = R::dispense('person');
		$accountant->job = 'accountant';
		$developer = R::dispense('person');
		$developer->job = 'developer';
		$salesman = R::dispense('person');
		$salesman->job = 'salesman';
		R::associate($painter, $accountant);
		R::associate($salesman, $accountant);
		R::associate($developer, $accountant);
		R::associate($salesman, $developer);
		asrt( getList( R::unrelated($salesman,"person"),"job" ), "painter,salesman" ) ;
		asrt( getList( R::unrelated($accountant,"person"),"job" ), "accountant" ) ;
		asrt( getList( R::unrelated($painter,"person"),"job" ), "developer,painter,salesman" ) ;
		R::associate($accountant, $accountant);
		R::associate($salesman, $salesman);
		R::associate($developer, $developer);
		R::associate($painter, $painter);
		asrt( getList( R::unrelated($accountant,"person"),"job" ), "" ) ;
		asrt( getList( R::unrelated($painter,"person"),"job" ), "developer,salesman" ) ;
		asrt( getList( R::unrelated($salesman,"person"),"job" ), "painter" ) ;
		asrt( getList( R::unrelated($developer,"person"),"job" ), "painter" ) ;
			
		R::nuke();
		$sheep = R::dispense('sheep');
		$sheep->name = 'Shawn';
		$sheep2 = R::dispense('sheep');
		$sheep2->name = 'Billy';
		$sheep3 = R::dispense('sheep');
		$sheep3->name = 'Moo';
		R::store($sheep3);
		R::associate($sheep,$sheep2);
		asrt(R::areRelated($sheep,$sheep2),true);
		asrt(R::areRelated($sheep,$sheep3),false);
		$pig = R::dispense('pig');
		asrt(R::areRelated($sheep,$pig),false);
		R::freeze(true);
		asrt(R::areRelated($sheep,$pig),false);
		R::freeze(false);
		$foo = R::dispense('foo');
		$bar = R::dispense('bar');
		$foo->id = 1;
		$bar->id = 2;
		asrt(R::areRelated($foo,$bar),false);
		
		
	}	
	
}