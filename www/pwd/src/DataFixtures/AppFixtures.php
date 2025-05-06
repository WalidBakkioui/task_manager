<?php

namespace App\DataFixtures;

use App\Entity\BlogPost;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{


    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    const Advert_NUMBER = 10;

    public function load(ObjectManager $manager): void
    {
        $user1 = new User();
        $user1->setEmail("first@localhost");
        $password = $this->passwordHasher->hashPassword(
            $user1,
            "user1"
        );
        $user1->setPassword($password);
        $user1->setRoles(['ROLE_USER']);

        $user2 = new User();
        $user2->setEmail("second@localhost");
        $password = $this->passwordHasher->hashPassword(
            $user2,
            "user2"
        );
        $user2->setPassword($password);
        $user2->setRoles(['ROLE_ADMIN']);

        $manager->persist($user1);
        $manager->persist($user2);

        $users = [$user1, $user2];

        $categories = ['acura', 'mercedes', 'bmw'];
        $categoriesEntities = [];
        foreach ($categories as $categoryName) {
            $category = new Category();
            $category->setName($categoryName);
            $manager->persist($category);
            $categoriesEntities[]= $category;
        }
//
//        $pictures = ['assets/images/2001-Acura-Integra-Type-R.jpg','assets/images/2002-BMW-M3-GTR.jpg',
//            'assets/images/2003-BMW-M5.jpg','assets/images/2012-Mercedes-Benz-C-63-AMG-Coupé-Black-Series.jpg',
//            'assets/images/2017-Acura-NSX.jpg','assets/images/2018-BMW-M5.jpg','assets/images/2021-Mercedes-AMG-ONE.jpg'];
//        $picturesEntities = [];
//        foreach ($pictures as $pictureName) {
//            $picture = new BlogPost();
//            $picture->setImg($pictureName);
//            $manager->persist($picture);
//            $picturesEntities[]= $picture;
//        }
//
//        $titles = ['Acura-Integra Type R', 'BMW M3 GTR', 'BMW M5'];
//        $titlesEntities = [];
//        foreach ($titles as $titleName) {
//            $title = new BlogPost();
//            $title->setTitle($titleName);
//            $manager->persist($title);
//            $titlesEntities[]= $title;
//        }
//
//
//        $maxPeoples = ['2', '4', '5'];
//        $maxPeoplesEntities = [];
//        foreach ($maxPeoples as $maxPeopleName) {
//            $maxPeople = new BlogPost();
//            $maxPeople->setMaxPeople($maxPeopleName);
//            $manager->persist($maxPeople);
//            $maxPeoplesEntities[]= $maxPeople;
//        }
//
//        $types = ['Integra Type R', 'M3 GTR', 'M5' ];
//        $typesEntities = [];
//        foreach ($types as $typeName) {
//            $type = new BlogPost();
//            $type->setType($typeName);
//            $manager->persist($type);
//            $typesEntities[]= $type;
//        }
//
//        $consumptions = ['6.1km / 1-litre', '10km / 1-litre','8km / 1-litre'];
//        $consumptionsEntities = [];
//        foreach ($consumptions as $consumptionName) {
//            $consumption = new BlogPost();
//            $consumption->setConsumption($consumptionName);
//            $manager->persist($type);
//            $consumptionsEntities[]= $consumption;
//        }
//
//        $boxs = ['manuelle', 'automatique'];
//        $boxsEntities = [];
//        foreach ($boxs as $boxName) {
//            $box = new BlogPost();
//            $box->setBox($boxName);
//            $manager->persist($type);
//            $boxsEntities[]= $box;
//        }
//
//        $year = ['2001', '2002', '2003', '2012', '2017', '2018', '2021'];
//        $yearEntities = [];
//        foreach ($year as $yearName) {
//            $years = new BlogPost();
//            $years->setYears($yearName);
//            $manager->persist($type);
//            $yearEntities[]= $years;
//        }



        $manager->flush();
//
//        for($i=0; $i< self::Advert_NUMBER; $i++) {
//            $post = new BlogPost();
//            $post->setCategory($categoriesEntities[$i % count($categoriesEntities) ]);
//            $post->setImg($picturesEntities[$i % count($picturesEntities) ]);
//            $post->setTitle($titlesEntities[$i % count($titlesEntities) ]);
//            $post->setMaxPeople($maxPeoplesEntities[$i % count($maxPeoplesEntities) ]);
//            $post->setType($typesEntities[$i % count($typesEntities) ]);
//            $post->setConsumption($consumptionsEntities[$i % count($consumptionsEntities) ]);
//            $post->setBox($boxsEntities[$i % count($boxsEntities) ]);
//            $post->setYears($yearEntities[$i % count($yearEntities) ]);
//////     Pas introduit dans le blogpost       $post->setUser($users[$i % count($users)]);
//            $manager->persist($post);
//        }
        $manager->flush();

    }
}
