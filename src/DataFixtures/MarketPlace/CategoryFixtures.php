<?php

namespace Inno\DataFixtures\MarketPlace;

use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Inno\Entity\MarketPlace\StoreCategory;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryFixtures extends Fixture
{

    /**
     * @var SluggerInterface
     */
    private SluggerInterface $slugger;

    /**
     * @param SluggerInterface $slugger
     */
    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    /**
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $this->loadProductCategories($manager);
    }

    /**
     *
     * @param ObjectManager $manager
     * @return void
     */
    private function loadProductCategories(ObjectManager $manager): void
    {

        foreach ($this->getProductCategoryData() as $key => $value) {
            $category = new StoreCategory();
            $category->setName($value['name']);
            $category->setSlug($this->slugger->slug($value['name'])->lower());
            $category->setDescription($value['description']);
            $category->setLevel(++$key);
            $category->setParent(null);
            $category->setCreatedAt(new DateTimeImmutable());
            $category->setDeletedAt(null);
            $manager->persist($category);

            if (count($value['parent'])) {

                foreach ($value['parent'] as $i => $children) {
                    $child = new StoreCategory();
                    $child->setParent($category);
                    $child->setName($children['name']);
                    $child->setSlug($this->slugger->slug($children['name'])->lower());
                    $child->setDescription($children['description']);
                    $child->setLevel(++$i);
                    $child->setCreatedAt(new DateTimeImmutable());
                    $child->setDeletedAt(null);
                    $manager->persist($child);
                }
            }
        }
        $manager->flush();
    }

    /**
     *
     * @return array
     */
    private function getProductCategoryData(): array
    {
        return [
            [
                'name' => 'Computers & Notebooks',
                'description' => 'Computers & Notebooks.',
                'parent' => [
                    [
                        'name' => 'Computers, nettops, monoblocs',
                        'description' => 'Computers, nettops, monoblocs',
                    ],
                    [
                        'name' => 'Notebooks',
                        'description' => 'Notebooks',
                    ],
                    [
                        'name' => 'Monitors',
                        'description' => 'Monitors',
                    ],
                    [
                        'name' => 'Networks equipment',
                        'description' => 'Networks equipment',
                    ],
                    [
                        'name' => 'Keyboards and mouses',
                        'description' => 'Keyboards and mouses',
                    ],
                    [
                        'name' => 'Office equipment',
                        'description' => 'Office equipment',
                    ],
                    [
                        'name' => 'Power banks',
                        'description' => 'Power banks',
                    ],
                    [
                        'name' => 'Headsets & microphones',
                        'description' => 'Headsets & microphones',
                    ],
                    [
                        'name' => 'Accompanying accessories',
                        'description' => 'Accompanying accessories',
                    ],
                ]],
            [
                'name' => 'Household Appliances',
                'description' => 'Household Appliances.',
                'parent' => [
                    [
                        'name' => 'Vacuum cleaners',
                        'description' => 'Vacuum cleaners',
                    ],
                    [
                        'name' => 'Washing machines',
                        'description' => 'Washing machines',
                    ],
                    [
                        'name' => 'Refrigeration & Food Safety',
                        'description' => 'Refrigeration & Food Safety',
                    ],
                    [
                        'name' => 'Household accessories',
                        'description' => 'Household accessories',
                    ],
                    [
                        'name' => 'Smart electronics',
                        'description' => 'Smart electronics',
                    ],
                    [
                        'name' => 'Microwave ovens',
                        'description' => 'Microwave ovens',
                    ],
                    [
                        'name' => 'Irons',
                        'description' => 'Irons',
                    ],
                ]],
            [
                'name' => 'Smartphones, TV and Electronics',
                'description' => 'Smartphones, TV and Electronics.',
                'parent' => [
                    [
                        'name' => 'TV & accessorise',
                        'description' => 'TV & accessorise',
                    ],
                    [
                        'name' => 'Cell phones & accessorises',
                        'description' => 'Cell phones & accessorises',
                    ],
                    [
                        'name' => 'Headphones',
                        'description' => 'Headphones',
                    ],
                    [
                        'name' => 'Photo & Video',
                        'description' => 'Photo & Video',
                    ],
                    [
                        'name' => 'Audio and accessories',
                        'description' => 'Audio and accessories',
                    ],
                    [
                        'name' => 'Tablets',
                        'description' => 'Tablets',
                    ],
                    [
                        'name' => 'Hand watches',
                        'description' => 'Hand watches',
                    ],
                    [
                        'name' => 'Projection equipments',
                        'description' => 'Projection equipments',
                    ],
                    [
                        'name' => 'Power stations',
                        'description' => 'Power stations',
                    ],
                    [
                        'name' => 'Smart watches',
                        'description' => 'Smart watches',
                    ],
                    [
                        'name' => 'Accessories',
                        'description' => 'Accessories',
                    ],
                ]],
            [
                'name' => 'Products for gamers',
                'description' => 'Products for gamers.',
                'parent' => [
                    [
                        'name' => 'Gaming gamepads',
                        'description' => 'Gaming gamepads',
                    ],
                    [
                        'name' => 'Gaming keyboards',
                        'description' => 'Gaming keyboards',
                    ],
                    [
                        'name' => 'Gaming mouses',
                        'description' => 'Gaming mouses',
                    ],
                    [
                        'name' => 'Gaming headphones',
                        'description' => 'Gaming headphones',
                    ],
                    [
                        'name' => 'Video game consoles',
                        'description' => 'Video game consoles',
                    ],
                    [
                        'name' => 'Accessories for gamers',
                        'description' => 'Accessories for gamers',
                    ],
                    [
                        'name' => 'Video games',
                        'description' => 'Video games',
                    ],
                    [
                        'name' => 'Accessories and souvenirs',
                        'description' => 'Accessories and souvenirs',
                    ],
                ]],
            [
                'name' => 'Products for home',
                'description' => 'Products for home.',
                'parent' => [
                    [
                        'name' => 'Home textiles',
                        'description' => 'Home textiles',
                    ],
                    [
                        'name' => 'Dishes ware',
                        'description' => 'Dishes ware',
                    ],
                    [
                        'name' => 'Furniture\'s',
                        'description' => 'Furniture\'s',
                    ],
                    [
                        'name' => 'Kids room',
                        'description' => 'Kids room',
                    ],
                    [
                        'name' => 'Decor for home',
                        'description' => 'Decor for home',
                    ],
                    [
                        'name' => 'Clocks',
                        'description' => 'Clocks',
                    ],
                    [
                        'name' => 'Mirrors',
                        'description' => 'Mirrors',
                    ],
                ]],
            [
                'name' => 'Automotive Tools',
                'description' => 'Automotive Tools.',
                'parent' => [
                    [
                        'name' => 'Jump Starters & Battery Chargers',
                        'description' => 'Jump Starters & Battery Chargers',
                    ],
                    [
                        'name' => 'Diagnostic, Test & Measurement Tools',
                        'description' => 'Diagnostic, Test & Measurement Tools',
                    ],
                    [
                        'name' => 'Electrical System Tools',
                        'description' => 'Electrical System Tools',
                    ],
                    [
                        'name' => 'Wheel & Tire Air Compressors',
                        'description' => 'Wheel & Tire Air Compressors',
                    ],
                    [
                        'name' => 'Garage & Shop Products',
                        'description' => 'Garage & Shop Products',
                    ],
                    [
                        'name' => 'Body Repair Tools',
                        'description' => 'Body Repair Tools',
                    ],
                    [
                        'name' => 'Tool Sets',
                        'description' => 'Tool Sets',
                    ],
                ]],
            [
                'name' => 'Plumbing and repair',
                'description' => 'Plumbing and repair.',
                'parent' => [
                    [
                        'name' => 'Well Supplies',
                        'description' => 'Well Supplies',
                    ],
                    [
                        'name' => 'Plumbing Fittings & Supports',
                        'description' => 'Plumbing Fittings & Supports',
                    ],
                    [
                        'name' => 'Plumbing Repair Kits',
                        'description' => 'Plumbing Repair Kits',
                    ],
                    [
                        'name' => 'Furniture\'s for Bathroom',
                        'description' => 'Furniture\'s for Bathroom',
                    ],
                    [
                        'name' => 'Furniture\'s for Toilet',
                        'description' => 'Tool Toilet',
                    ],
                    [
                        'name' => 'Bathroom & Toilet Mirrors',
                        'description' => 'Bathroom & Toilet Mirrors',
                    ],

                ]],
            [
                'name' => 'Sports & Leisure',
                'description' => 'Sports & Leisure.',
                'parent' => [
                    [
                        'name' => 'Sports equipment',
                        'description' => 'Sports equipment',
                    ],
                    [
                        'name' => 'Sports gear',
                        'description' => 'Sports gear',
                    ],
                    [
                        'name' => 'Hunting and fishing',
                        'description' => 'Hunting and fishing',
                    ],
                    [
                        'name' => 'Bicycles & motorcycles',
                        'description' => 'Bicycles & motorcycles',
                    ],
                    [
                        'name' => 'Sport simulators',
                        'description' => 'Sport simulators',
                    ],
                ]],
            [
                'name' => 'Beauty and Health',
                'description' => 'Beauty and Health.',
                'parent' => [
                    [
                        'name' => 'Dermatocosmetics',
                        'description' => 'Dermatocosmetics',
                    ],
                    [
                        'name' => 'Perfumery',
                        'description' => 'Perfumery',
                    ],
                    [
                        'name' => 'Decorative cosmetics',
                        'description' => 'Decorative cosmetics',
                    ],
                    [
                        'name' => 'Gift sets of cosmetics',
                        'description' => 'Gift sets of cosmetics',
                    ],
                    [
                        'name' => 'Facial',
                        'description' => 'Facial',
                    ],
                    [
                        'name' => 'Hair care',
                        'description' => 'Hair care',
                    ],
                    [
                        'name' => 'Cosmetics for children',
                        'description' => 'Cosmetics for children',
                    ],
                    [
                        'name' => 'Body care',
                        'description' => 'Body care',
                    ],
                    [
                        'name' => 'Oral care',
                        'description' => 'Oral care',
                    ],
                    [
                        'name' => 'Shaving products',
                        'description' => 'Shaving products',
                    ],
                    [
                        'name' => 'Hair dyeing and curling',
                        'description' => 'Hair dyeing and curling',
                    ],
                ]],
            [
                'name' => 'Goods for Kids',
                'description' => 'Goods for Kids.',
                'parent' => [
                    [
                        'name' => 'Toys',
                        'description' => 'Toys',
                    ],
                    [
                        'name' => 'LEGO constructors',
                        'description' => 'LEGO constructors',
                    ],
                    [
                        'name' => 'Creativity',
                        'description' => 'Creativity',
                    ],
                    [
                        'name' => 'Books',
                        'description' => 'Books',
                    ],
                    [
                        'name' => 'Diapers',
                        'description' => 'Diapers',
                    ],
                    [
                        'name' => 'Food and feeding',
                        'description' => 'Food and feeding',
                    ],
                    [
                        'name' => 'Strollers and car seats',
                        'description' => 'Strollers and car seats',
                    ],
                    [
                        'name' => 'Walks and rest',
                        'description' => 'Walks and rest',
                    ],
                ]],
            [
                'name' => 'Handmade',
                'description' => 'Handmade.',
                'parent' => [
                    [
                        'name' => 'Artwork',
                        'description' => 'Artwork',
                    ],
                    [
                        'name' => 'Handmade accessories',
                        'description' => 'Handmade accessories',
                    ],
                    [
                        'name' => 'Wooden Toys',
                        'description' => 'Wooden Toys',
                    ],
                    [
                        'name' => 'Clothing',
                        'description' => 'Clothing',
                    ],
                    [
                        'name' => 'Jewellery',
                        'description' => 'Jewellery',
                    ],
                    [
                        'name' => 'Baby',
                        'description' => 'Baby',
                    ],
                    [
                        'name' => 'Party Supplies',
                        'description' => 'Party Supplies',
                    ],
                ]],
        ];
    }
}