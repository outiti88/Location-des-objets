<?php

namespace App\DataFixtures;

use App\Entity\Ad;
use App\Entity\Booking;
use App\Entity\Category;
use App\Entity\City;
use App\Entity\Comment;
use Faker\Factory;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\Image;
use App\Entity\Premium;
use App\Entity\SubCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('FR-fr');

        $adminRole = new Role;
        $adminRole->setTitle('ROLE_ADMIN');
        $manager->persist($adminRole);

        $adminUser = new User;
        $adminUser->setFirstName('Wassim')
            ->setLastName('Abbari')
            ->setEmail('admin@symfony.com')
            ->setHash($this->encoder->encodePassword($adminUser, 'password'))
            ->setPicture("https://avatars.io/twitter/LioorC")
            ->setIntroduction($faker->sentence())
            ->setDescription('<p>' . join('</p><p>', $faker->paragraphs(5)) . '</p>')
            ->addUserRole($adminRole);
        $manager->persist($adminUser);

        //Nous gérons les utilisateurs
        $users = [];
        $genres = ['male', 'female'];

        for ($i = 1; $i <= 10; $i++) {

            $user = new User;

            $genre = $faker->randomElement($genres);

            $picture = 'https://randomuser.me/api/portraits/';

            $picture .= ($genre == 'male' ? 'men/' : 'women/') . $i . '.jpg';

            $hash = $this->encoder->encodePassword($user, 'password');

            $user->setFirstName($faker->firstname($genre))
                ->setLastName($faker->lastname)
                ->setEmail($faker->email)
                ->setIntroduction($faker->sentence())
                ->setDescription('<p>' . join('</p><p>', $faker->paragraphs(5)) . '</p>')
                ->setHash($hash)
                ->setPicture($picture);

            $manager->persist($user);
            $users[] = $user;
        }

        //Nous gérons les catégories
        $categories = array("bateau", "camping-car / van", "electromenager", "equipement bebe");
        $subCategories["bateau"] = array("bateau à moteur", "bateau à quai", "bateau avec skipper");
        $subCategories["camping-car / van"] = array("camping-car", "caravane", "fourgon aménagé");
        $subCategories["electromenager"] = array("barbecue", "blender", "bouilloire");
        $subCategories["equipement bebe"] = array("lit bébé", "chaise haute", "couffin");

        $subCategoryObjects = [];

        foreach ($categories as $c) {
            $category = new Category;

            $category->setTitle($c);
            foreach ($subCategories[$c] as $s) {
                $subCategory = new SubCategory;
                $subCategory->setTitle($s)
                    ->setCategory($category);

                $subCategoryObjects[] = $subCategory;

                $manager->persist($subCategory);
            }
            $manager->persist($category);
        }
        //Nous gérons les villes 
        $cities = array(
            "Tout le maroc", "Casablanca", "Rabat", "Marrakesh", "Tanger", "Tetouan", "Agadir",
            "Nador", "Al Hoceima", "Béni mellal", "El Jadida", "Errachidia", "Fes", "Kenitra",
            "Khénifra", "Khouribga", "Larache", "Mèknes", "Ouarzazate", "Oujda", "Safi",
            "Settat", "Salé", "Taza", "Mohammedia"
        );
        $cityObjects = [];
        foreach ($cities as $c) {
            $city = new City;
            $city->setName($c);
            $cityObjects[] = $city;
            $manager->persist($city);
        }

        //Nous gérons les annonces
        for ($i = 0; $i < 30; $i++) {
            $title = $faker->sentence();
            $coverImage = "https://picsum.photos/1950/550?random=" . mt_rand(1, 55000);
            $introduction = $faker->paragraph(2);
            $content    = '<p>' . join('</p><p>', $faker->paragraphs(5)) . '</p>';

            $user = $users[mt_rand(0, count($users) - 1)];
            $subCategory = $subCategoryObjects[mt_rand(0, count($subCategoryObjects) - 1)];

            $ad = new Ad;
            $ad->setTitle($title)
                ->setCoverImage($coverImage)
                ->setIntroduction($introduction)
                ->setContent($content)
                ->setPrice(mt_rand(40, 200))
                ->setAuthor($user)
                ->setSubCategory($subCategory)
                ->setCity($cityObjects[mt_rand(1, count($cityObjects) - 1)]);

            //Nous gérons les annonces premium
            $premium = new Premium;
            $value = mt_rand(0, 1);

            if ($value) {
                $durationPremium = array(7, 15, 30);
                $startDate = new \DateTime();
                $endDate = (clone $startDate)->modify('+' . $durationPremium[mt_rand(0, 2)] . ' days');
                $premium->setValue($value)
                    ->setStartDate($startDate)
                    ->setEndDate($endDate)
                    ->setAd($ad);

                $manager->persist($premium);
            } else {
                $premium->setValue($value)
                    ->setAd($ad);

                $manager->persist($premium);
            }



            //Nous gérons les images des annonces
            for ($j = 1; $j < mt_rand(2, 5); $j++) {
                $image = new Image;

                $image->setUrl("https://picsum.photos/640/480?random=" . mt_rand(0, 55000))
                    ->setCaption($faker->sentence())
                    ->setAd($ad);

                $manager->persist($image);
            }

            //Nous gérons les reservations
            for ($j = 1; $j <= mt_rand(0, 10); $j++) {
                $booking = new Booking;

                $createdAt = $faker->dateTimeBetween('-6 months');
                $startDate = $faker->dateTimeBetween($createdAt);

                $duration = mt_rand(3, 10);
                $endDate = (clone $startDate)->modify("+$duration days");

                $amount = $ad->getPrice() * $duration;
                $booker = $users[mt_rand(0, count($users) - 1)];

                $comment = $faker->paragraph();

                $booking->setBooker($booker)
                    ->setAd($ad)
                    ->setStartDate($startDate)
                    ->setEndDate($endDate)
                    ->setCreatedAt($createdAt)
                    ->setComment($comment)
                    ->setAmount($amount);

                $manager->persist($booking);

                //Gestion des commentaires
                if (mt_rand(0, 1)) {
                    $comment = new Comment;
                    $comment->setContent($faker->paragraph())
                        ->setRating(mt_rand(1, 5))
                        ->setAuthor($booker)
                        ->setAd($ad);

                    $manager->persist($comment);
                }
            }

            $manager->persist($ad);
        }

        $manager->flush();
    }
}
