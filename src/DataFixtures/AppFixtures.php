<?php

namespace Inno\DataFixtures;

use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Inno\DataFixtures\MarketPlace\Fixtures;
use Inno\Entity\{Category, Faq, User, UserDetails, UserSocial};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{

    private SluggerInterface $slugger;

    /**
     * @param UserPasswordHasherInterface $passwordHasher
     * @param SluggerInterface $slugger
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        SluggerInterface                             $slugger,
    )
    {
        $this->slugger = $slugger;
    }

    /**
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $this->loadCategories($manager);
        $this->loadQuestions($manager);
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            Fixtures::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     * @return void
     */
    private function loadUsers(ObjectManager $manager): void
    {
        $userDetails = $this->getUserDetailsData();

        $key = 0;
        foreach (self::getUserData() as [$password, $email, $roles, $ip]) {
            $user = new User();
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setEmail($email);
            $user->setRoles($roles);
            $user->setIp($ip);
            $user->setCreatedAt(new DateTime());
            $user->setDeletedAt(null);

            $manager->persist($user);

            $details = new UserDetails();
            $details->setUser($user)
                ->setFirstName($userDetails[$key]['first_name'])
                ->setLastName($userDetails[$key]['last_name']);

            $manager->persist($details);

            $social = new UserSocial();
            $social->setDetails($details);

            $manager->persist($social);

            $key++;
        }
        $manager->flush();
    }

    /**
     *
     * @param ObjectManager $manager
     * @return void
     */
    private function loadCategories(ObjectManager $manager): void
    {
        foreach ($this->getCategoryData() as [$name, $description, $position]) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug($this->slugger->slug($name)->lower()->toString());
            $category->setDescription($description);
            $category->setPosition($position);
            $category->setCreatedAt(new DateTime());
            $category->setDeletedAt(null);
            $manager->persist($category);
        }
        $manager->flush();
    }

    /**
     *
     * @param ObjectManager $manager
     * @return void
     */
    private function loadQuestions(ObjectManager $manager): void
    {
        foreach ($this->getQuestionsData() as [$title, $content]) {
            $faq = new Faq();
            $faq->setTitle($title)->setContent($content)->setVisible(1);
            $manager->persist($faq);
        }

        $manager->flush();
    }

    /**
     *
     * @return array
     */
    public static function getUserData(): array
    {
        return [
            ['7212104', 'alexandershtyher@gmail.com', [User::ROLE_ADMIN], '0.0.0.0'],
            ['7212104', 'autoportal@email.ua', [User::ROLE_USER], '0.0.0.0'],
            ['7212104', 'alexfer@online.ua', [User::ROLE_USER], '0.0.0.0'],
            ['joanna', 'joanna@example.com', [User::ROLE_USER], '0.0.0.0'],
            ['bobby', 'bobby@example.com', [User::ROLE_USER], '0.0.0.0'],
            ['UserTest', 'usertest@example.com', [User::ROLE_USER], '0.0.0.0'],
        ];
    }

    /**
     *
     * @return array
     */
    private function getUserDetailsData(): array
    {
        return [
            ['first_name' => 'Alexander', 'last_name' => 'Sh'],
            ['first_name' => 'Auto portal', 'last_name' => 'Last name'],
            ['first_name' => 'Олександр', 'last_name' => 'Штихер'],
            ['first_name' => 'Joanna', 'last_name' => 'Smith'],
            ['first_name' => 'Bobby', 'last_name' => 'Smith'],
            ['first_name' => 'User', 'last_name' => 'Test'],
        ];
    }

    /**
     *
     * @return array
     */
    private function getCategoryData(): array
    {
        return [
            ['Main', 'The main blogs is the most important one of several or more similar things.', 1],
            ['Food', 'Food is one of the most popular blog categories. Food blogs range from baking blogs to vegan blogs to baby food blogs. If you can think of a food topic, chances are that there is at least one blog about it. Some of these blogs are the grandchildren of the original Mommy blogs. They began as Mommy blogs, then specialized in their more successful topics of cooking, baking, or other food topics.', 2],
            ['Travel', 'Traveling is popular with most demographics, especially retired people. Travel blogs bring the richness of exotic locations to viewers through imagery, video, and streaming while offering insight into the best way to travel in different countries, states, or even locally.', 3],
            ['Health and fitness', 'Health and fitness blogs have become well-liked by several demographics, and each blog caters to a specific audience. Some focus on medical issues like diabetes or heart disease. Others focus on how a demographic, like retirees, can stay healthy in their daily life. At the other end of the spectrum, you\'ll find blogs about professional weightlifting, yoga, or even the best children\'s fitness routines.', 4],
            ['Lifestyle', 'Lifestyle blogs generally have an overriding theme like living in the country, small towns, life in a region of the country, living with kids in a downtown city, or just about any living situation you can find. These tend to be more upscale because many of the products and services that sponsor lifestyle blogs are marketing upper-scale brands.', 5],
            ['Fashion and beauty', 'Since fashion and beauty trends change often, blogs about these topics never run out of things to cover. Many of these blogs are niche-focused. For instance, a blog that discusses how to select thrift shop finds and then turn them into trendy wardrobe additions rather than general women\'s fashion.', 6],
            ['DIY craft', 'The internet has been good for DIYers. You can find a blog post or YouTube video about almost any kind of DIY project, large or small. Using images or video, DIY craft bloggers can demonstrate and explain how their audience can complete the same craft, including what materials they need to buy, and how to do it step-by-step.', 7],
            ['Parenting', 'Parenting is a huge job, and no one knows that better than another parent. Parenting blogs were originally an offshoot of the Mommy blogs, except they\'ve become specialized. You can find blogs on how to homeschool your children, make clothing for them, the best ways to get kids to eat nutritional foods, and what baby furniture is the best to buy for your budget.', 8],
            ['Business', 'We\'ve already discussed why small businesses need to have a blog, but the truth is that almost every type of business has a blog in one form or another. And blogging isn\'t just for B2C companies. B2B companies also find that blogs help with website optimization for search engines and qualifying new leads.', 9],
            ['Personal finance', 'Many people are interested in ways to earn, save, and grow their money. This is especially true for people who are retired or near retirement. They want to learn ways to save money, and how to make money last longer. These types of blogs usually find a niche for their audience. Some may specialize in stocks, bonds, and investing, while others can be about how to start saving for retirement in your 20s and 30s.', 10],
            ['Sports', 'Sports fans love to learn about their favorite teams and players, therefore it\'s not surprising that you can find lots of sports blogs on one sport, a range of local sports, or even sports camps to help participants improve their skills. Some blogs focus on the development of specific players or teams, while others report what is happening globally in sports. Blogs that focus on just one sport may only be active while that sport is in season, or they find other topics to share with their followers during the off-season..', 11],
            ['Music', 'Music blogs are where fans, artists, and critics come together to talk about everything from new releases to the latest trends in the industry. A music blog is the perfect place to share your insights and connect with like-minded people, whether you\'re a: Musician, Producer, Passionate listener.', 12],
            ['Art and Design', 'Think of an art or design blog as an extension of your creativity. You can build a "digital museum" displaying your work regardless of your art medium. But art blogs are not just for exhibiting and discussing art. Consider offering your courses and tutorials to other creatives looking to learn new skills, as Brit dot Design does. To help enroll more interested learners, share your qualifications and unique experiences in your About Me section.', 13],
            ['News', 'Today, news is reported not only on TV, radio, and prominent publications but also on independent blogs. News posts keep readers informed and up-to-date on critical issues on a local, national, or even global level, and they are a new form of journalism.', 14],
            ['Movie', 'Do you go to the movies for popcorn or to analyze the directors\' use of cinematography, plot development, and musical scores? If you fall into the latter, maybe you\'re ready to get cast as a "movie blogger."', 15],
            ['Podcast', 'The content for this blog includes the embedded podcast (usually audio or video or both, from whatever platform it\'s supplied from).', 16],
        ];
    }

    /**
     *
     * @return array
     */
    private function getQuestionsData(): array
    {
        return [
            ['Where do your developers come from?', '<b>Techspace works</b> from Ukraine, Poland, Hungary, Bulgaria, Serbia, Romania, Croatia, Spain, Portugal and Macedonia.'],
            ['Do your developers have experience with Agile?', 'Yes. Our developers are trained in Agile, SCRUM and Kanban. Our specialists are trained and experienced in project-based working according to the Agile methodology, so they can also join your organization immediately.'],
            ['When is Nearshore right for my organization?', 'Nearshore is suitable for virtually all organizations. Through Corona, we have all adapted to working from home and remotely and have become accustomed to online meetings. As a result, scheduling work and sharing updates through online platforms has become increasingly normal and has removed the biggest barrier to nearshoring. So as long as you English speaks and has an idea of what you want your developer or team to develop, nearshore development is right for your organization.'],
            ['What happens if we are not satisfied with a developer?', 'If you are not satisfied with the performance of a developer, you initially report this to us. We then engage with him/her to resolve the problem. If it appears that the problem is not resolvable between you and the developer, we will within 2 to 4 weeks find a suitable replacement for you.'],
            ['Can you support me in managing my team?', 'We have 15 years of experience setting up collaborations and remote teams. We are happy to advise you and share best practices with you.'],
            ['What is Nearshore?', 'Nearshore is a technical term for supplementing or setting up a team, abroad but close to home. In contrast to Outsourcing or Outstaffing, Nearshore is really a collaborative model between customer and supplier, where our role is to find and retain the specialists you need. Where as the client is responsible for the direction and risks of the project. The emphasis is on collaboration as Nearshore team members work closely with your internal staff in order to deliver your projects quickly and with high quality.'],
            ['We need a large development team, can you help?', 'Of course, we would love to get in touch with you. Depending on your needs, we can indicate whether we can help you with your request or not. This will mainly depend on how many developers, the techniques and how quickly the team needs to start. Feel free to contact us at info@techspace.com.'],
            ['I am looking for only 1 developer, can you help me?', 'Of course, our services can be purchased per 1 FTE. For support services, such as DevOps– or QA as a service, a minimum of 16 hours per week applies. A specialist will do the maximum feasible with the hours you purchase. So by taking less hours, you also have less capacity or operational clout to achieve your goals. Do you find it difficult to determine how many hours of support you need for your projects? Then contact us without obligation at info@techspace.com.'],
            ['Can my team come to our headquarters on a business trip?', 'Yes. We can completely take care of this for you by arranging airfare, visas, and lodging for the period you have your team on your <b>head office</b> want to invite. Before we arrange everything, we will of course coordinate all costs with you. We do not charge a fee for this service, the costs incurred can be paid by you directly.'],
        ];
    }

}
