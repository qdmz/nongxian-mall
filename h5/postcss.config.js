module.exports = {
  plugins: {
    'postcss-px-to-viewport': {
      // 移动端兜底：将 px 转 rem（设计稿基准 375px，html 为 37.5px）
      viewportWidth: 375,
      unitPrecision: 6,
      viewportUnit: 'rem',
      fontViewportUnit: 'rem',
      selectorBlackList: ['.ignore', '._ignore', 'van-popup', '.tc-nav .van-nav-bar__title', '.van-nav-bar .van-nav-bar__title'],
      minPixelValue: 1,
      mediaQuery: false
    }
  }
}
