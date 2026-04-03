import { createRouter, createWebHistory } from "vue-router";
import type { RouteRecordRaw } from "vue-router";

import AppLayout from "../components/layout/AppLayout.vue";

import Dashboard from "../views/Dashboard.vue";
import Courses from "../views/Courses.vue";
import Companies from "../views/Companies.vue";
import Participants from "../views/Participants.vue";
import Groups from "../views/Groups.vue";
import GroupDetail from "../views/GroupDetail.vue";
import Specifications from "../views/Specifications.vue";
import GanttChart from "../views/GanttChart.vue";

const routes: Array<RouteRecordRaw> = [
  {
    path: "/",
    component: AppLayout, 
    children: [
      { path: "", name: "Dashboard", component: Dashboard },
      { path: "courses", name: "Courses", component: Courses },
      { path: "companies", name: "Companies", component: Companies },
      { path: "participants", name: "Participants", component: Participants },
      { path: "groups", name: "Groups", component: Groups },
      { path: "groups/:id", name: "GroupDetail", component: GroupDetail, props: true },
      { path: "specifications", name: "Specifications", component: Specifications },
      { path: "gantt", name: "GanttChart", component: GanttChart },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;