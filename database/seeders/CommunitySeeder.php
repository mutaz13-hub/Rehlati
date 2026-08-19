<?php

namespace Database\Seeders;

use App\Enums\CommunityMemberRole;
use App\Enums\CommunityMemberStatus;
use App\Enums\CommunityVisibility;
use App\Enums\PostType;
use App\Enums\VoteType;
use App\Models\Comment;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\CommunityMessage;
use App\Models\Post;
use App\Models\User;
use App\Models\VoteTotal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereHas('roles', fn ($q) => $q->where('name', 'user'))->get();

        if ($users->isEmpty()) {
            return;
        }

        $communities = $this->seedCommunities($users);
        $this->seedMembers($communities, $users);
        $posts = $this->seedPosts($communities, $users);
        $this->seedComments($posts, $users);
        $this->seedMessages($communities, $users);
    }

    private function seedCommunities($users): Collection
    {
        $data = [
            [
                'name' => 'Syria Travel Tips',
                'description' => 'Share and discover the best travel tips for exploring Syria — hidden gems, local food spots, and must-visit landmarks.',
                'visibility' => CommunityVisibility::PUBLIC,
            ],
            [
                'name' => 'Damascus Explorers',
                'description' => 'A community for anyone who loves Damascus — its history, its streets, its food, and its people.',
                'visibility' => CommunityVisibility::PUBLIC,
            ],
            [
                'name' => 'Aleppo Heritage Walks',
                'description' => 'Documenting and celebrating the rich heritage of Aleppo through photos, stories, and walking tours.',
                'visibility' => CommunityVisibility::PUBLIC,
            ],
            [
                'name' => 'Syrian Foodie Network',
                'description' => 'From kibbeh to knafeh — share recipes, restaurant finds, and food photography from across Syria.',
                'visibility' => CommunityVisibility::PUBLIC,
            ],
            [
                'name' => 'Off-the-Beaten-Path Syria',
                'description' => 'For adventurous travelers looking to explore lesser-known destinations in Syria.',
                'visibility' => CommunityVisibility::PRIVATE,
            ],
            [
                'name' => 'Coastal Syria Lovers',
                'description' => 'Latakia, Tartus, and the Mediterranean coast — beaches, sunsets, and seaside dining.',
                'visibility' => CommunityVisibility::PUBLIC,
            ],
        ];

        $communities = collect();
        foreach ($data as $i => $item) {
            $owner = $users[$i % $users->count()];
            $communities->push(Community::create([
                'uuid' => (string) Str::uuid(),
                'name' => $item['name'],
                'description' => $item['description'],
                'visibility' => $item['visibility'],
                'owner_id' => $owner->id,
            ]));
        }

        return $communities;
    }

    private function seedMembers($communities, $users): void
    {
        $statuses = [CommunityMemberStatus::APPROVED, CommunityMemberStatus::APPROVED, CommunityMemberStatus::APPROVED, CommunityMemberStatus::PENDING];

        foreach ($communities as $community) {
            $otherUsers = $users->where('id', '!=', $community->owner_id)->shuffle();
            $memberCount = fake()->numberBetween(4, min(8, $otherUsers->count()));

            for ($i = 0; $i < $memberCount; $i++) {
                $user = $otherUsers[$i];
                $role = $i === 0
                    ? CommunityMemberRole::ADMIN
                    : CommunityMemberRole::MEMBER;

                CommunityMember::create([
                    'community_id' => $community->id,
                    'user_id' => $user->id,
                    'role' => $role,
                    'status' => $statuses[array_rand($statuses)],
                    'joined_at' => fake()->dateTimeBetween('-6 months', 'now'),
                ]);
            }
        }
    }

    private function seedPosts($communities, $users): Collection
    {
        $postBodies = [
            'text' => [
                'Just arrived in Damascus and the Old City is absolutely breathtaking. Any restaurant recommendations near Straight Street?',
                'Has anyone done the drive from Latakia to Tartus recently? How are the road conditions?',
                'Tip: Always carry small denominations of SYP. Many local shops don\'t have change for large bills.',
                'Can anyone recommend a good local guide in Aleppo? Planning a trip next month.',
                'The sunsets over the Euphrates are something else. Highly recommend a evening boat ride if you get the chance.',
                'Sharing my itinerary for a 7-day trip through central Syria. Happy to answer questions!',
                'Found an incredible little bakery in Homs that makes the best kunafeh I\'ve ever had. Will post the location soon.',
                'What\'s the best time of year to visit the coast? I\'m thinking late September.',
                'Looking for travel buddies for a hiking trip in the Syria-Lebanon border mountains.',
                'Pro tip: Download offline maps before you go. Cell coverage can be spotty in rural areas.',
                'Just tried kibbeh bil-saynieh in a small village near Hama — absolutely incredible.',
                'Anyone know if the Citadel of Aleppo is open for visitors right now?',
                'The souks in Damascus are a treasure trove. Spent three hours and barely scratched the surface.',
                'My kids loved the adventure park near Tartus. Highly recommend for families!',
                'Does anyone have experience booking hotels through this app? How was the process?',
            ],
            'image' => [
                'Morning view from my hotel balcony in the Old City of Damascus.',
                'The Great Mosque of Aleppo — captured at golden hour.',
                'Street food scene in Homs. The falafel here is unmatched.',
                'Crystal clear waters at Tartus beach. Syrian riviera at its finest.',
                'The ancient Roman theater in Bosra. Such an underrated site.',
            ],
            'video' => [
                'Quick walking tour through the souks of Aleppo. The architecture is stunning.',
                'Drone footage of the coast from Latakia to Tartus.',
            ],
            'audio' => [
                'Recorded a short conversation with a local artisan in Damascus. His family has been making mosaic art for generations.',
                'Ambient sounds from a Damascus coffee shop — the perfect study background.',
            ],
        ];

        $posts = collect();
        foreach ($communities as $community) {
            $communityUsers = $users->filter(fn ($u) => $u->id !== $community->owner_id)->shuffle();
            $communityUsers->push($users->firstWhere('id', $community->owner_id));
            $numPosts = fake()->numberBetween(6, 12);

            for ($i = 0; $i < $numPosts; $i++) {
                $type = fake()->randomElement(PostType::cases());
                $body = match ($type) {
                    PostType::TEXT => $postBodies['text'][array_rand($postBodies['text'])],
                    PostType::IMAGE => $postBodies['image'][array_rand($postBodies['image'])],
                    PostType::VIDEO => $postBodies['video'][array_rand($postBodies['video'])],
                    PostType::AUDIO => $postBodies['audio'][array_rand($postBodies['audio'])],
                };

                $post = Post::create([
                    'community_id' => $community->id,
                    'user_id' => $communityUsers->random()->id,
                    'type' => $type,
                    'body' => $body,
                ]);

                $posts->push($post);

                $this->seedVotes($post, $users, Post::MORPH_KEY);
            }
        }

        return $posts;
    }

    private function seedComments($posts, $users): void
    {
        $commentBodies = [
            'Great tip! I\'ll definitely try that on my next visit.',
            'I was there last summer and it was amazing.',
            'Can you share more details about the location?',
            'This is exactly what I needed. Thanks for sharing!',
            'I disagree — I think the food in Aleppo is better.',
            'Beautiful photo! What camera did you use?',
            'Adding this to my must-visit list.',
            'Has anyone else tried this? I\'d love to hear other opinions.',
            'The people there are so welcoming and friendly.',
            'We visited last year and had a wonderful experience.',
            'How long did the trip take? I\'m planning a similar route.',
            'That looks incredible. Syria never disappoints.',
            'I lived in that area for years and still discovered new things every day.',
            'Thanks for the honest review. Very helpful.',
            'This is why I love this community — always finding hidden gems here.',
        ];

        foreach ($posts as $post) {
            $commentCount = fake()->numberBetween(2, 6);
            $commenters = $users->shuffle()->take($commentCount);

            foreach ($commenters as $commenter) {
                $type = fake()->randomElement(PostType::cases());
                $comment = Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $commenter->id,
                    'type' => $type,
                    'body' => $commentBodies[array_rand($commentBodies)],
                ]);

                $this->seedVotes($comment, $users, Comment::MORPH_KEY);
            }
        }
    }

    private function seedMessages($communities, $users): void
    {
        $messageBodies = [
            'Hey everyone! Excited to be part of this group.',
            'Welcome! You\'re going to love it here.',
            'Does anyone have plans for the weekend?',
            'I just booked a hotel in Damascus for next week!',
            'Great news! How did you find the rates?',
            'The weather forecast looks perfect for travel.',
            'Has anyone tried the new restaurant near the Umayyad Mosque?',
            'Sharing some photos from my trip last weekend.',
            'What a beautiful country we live in.',
            'Anyone up for a group trip to Palmyra next month?',
            'I can recommend a great driver if anyone needs one.',
            'Just passed through Hama — the norias are spinning!',
            'Can someone share the link to that hotel booking app?',
            'Happy Friday everyone! Wishing you all a great weekend.',
            'The cherry season on the coast is starting. Best time to visit!',
            'Looking for a roommate for a trip to Latakia next week.',
            'That sunset photo is stunning! Where was it taken?',
            'I tried the kibbeh place someone recommended — it was fantastic!',
            'Safe travels to everyone heading out this weekend.',
            'This community is the best thing that happened to Syrian tourism.',
        ];

        foreach ($communities as $community) {
            $memberIds = $community->memberPivots()
                ->where('status', CommunityMemberStatus::APPROVED)
                ->pluck('user_id')
                ->push($community->owner_id)
                ->unique()
                ->values();

            if ($memberIds->isEmpty()) {
                continue;
            }

            $messageCount = fake()->numberBetween(8, 18);
            $baseTime = fake()->dateTimeBetween('-3 months', '-1 month');

            for ($i = 0; $i < $messageCount; $i++) {
                CommunityMessage::create([
                    'community_id' => $community->id,
                    'user_id' => $memberIds->random(),
                    'body' => $messageBodies[array_rand($messageBodies)],
                    'created_at' => (clone $baseTime)->modify("+{$i} hours +".random_int(0, 59).' minutes'),
                ]);
            }
        }
    }

    private function seedVotes($votable, $users, string $morphType): void
    {
        $voters = $users->shuffle()->take(fake()->numberBetween(0, min(8, $users->count())));

        $upCount = 0;
        $downCount = 0;

        foreach ($voters as $voter) {
            $voteType = fake()->randomElement(VoteType::cases());
            $votable->votes()->updateOrCreate(
                ['user_id' => $voter->id],
                ['vote' => $voteType]
            );

            if ($voteType === VoteType::UP) {
                $upCount++;
            } else {
                $downCount++;
            }
        }

        VoteTotal::updateOrCreate(
            ['voteable_type' => $morphType, 'voteable_id' => $votable->id, 'vote_type' => VoteType::UP],
            ['count' => $upCount]
        );

        VoteTotal::updateOrCreate(
            ['voteable_type' => $morphType, 'voteable_id' => $votable->id, 'vote_type' => VoteType::DOWN],
            ['count' => $downCount]
        );
    }
}
