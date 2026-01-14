import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // تحسينات للأداء
  reactStrictMode: true,
  // turbopack: {
  //   root: process.cwd(),
  // },
  // eslint: {
  //   ignoreDuringBuilds: true,
  // },
  // typescript: {
  //   ignoreBuildErrors: true,
  // },
  // استخدام Webpack بدلاً من Turbopack للبناء
  // webpack: (config) => {
  //   config.cache = false;
  //   return config;
  // },
  async rewrites() {
    return [
      { source: "/dashboard/users", destination: "/users" },
      { source: "/dashboard/users/create", destination: "/users/create" },
      { source: "/dashboard/users/:id", destination: "/users/:id" },
      { source: "/dashboard/users/:id/edit", destination: "/users/:id/edit" },
    ];
  },
};

export default nextConfig;
