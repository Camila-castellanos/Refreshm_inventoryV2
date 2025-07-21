declare interface Window {
  route: (...args: any[]) => any;
}

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    $route: (...args: any[]) => any;
  }
}