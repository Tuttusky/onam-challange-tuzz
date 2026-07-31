import { createRouter, createWebHistory } from 'vue-router';
import { useAnimation } from '@/composables/useAnimation';

const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('@/pages/HomePage.vue'),
        meta: { title: 'Home' },
    },
    {
        path: '/personalize/:slug',
        redirect: (to) => ({
            name: 'play',
            params: { slug: to.params.slug },
            query: to.query,
        }),
    },
    {
        path: '/play/:slug',
        name: 'play',
        component: () => import('@/pages/PlayPage.vue'),
        meta: { title: 'Play' },
    },
    {
        path: '/share/:token',
        name: 'share',
        component: () => import('@/pages/SharePage.vue'),
        meta: { title: 'Share' },
    },
    {
        path: '/challenge/:token',
        name: 'challenge',
        component: () => import('@/pages/ChallengePage.vue'),
        meta: { title: 'Challenge' },
    },
    {
        path: '/pottu/challenge/:token',
        name: 'pottu-challenge',
        component: () => import('@/pages/ChallengePage.vue'),
        meta: { title: 'Pottu Challenge' },
    },
    {
        path: '/result/:token/:resultUuid?',
        name: 'result',
        component: () => import('@/pages/ResultPage.vue'),
        meta: { title: 'Result' },
    },
    {
        path: '/leaderboard',
        name: 'leaderboard',
        component: () => import('@/pages/LeaderboardPage.vue'),
        meta: { title: 'Leaderboard' },
    },
    {
        path: '/page/:slug',
        name: 'cms',
        component: () => import('@/pages/CmsPage.vue'),
        meta: { title: 'Page' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

router.afterEach((to) => {
    const baseTitle = window.__APP_SEO__?.title || 'Onam Dare Challenge';
    document.title = to.meta.title ? `${to.meta.title} | ${baseTitle}` : baseTitle;
    useAnimation().pageEnter();
});

export default router;
