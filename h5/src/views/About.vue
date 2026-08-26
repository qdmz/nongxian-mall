<template>
  <div class="about-page">
    <van-nav-bar title="关于我们" left-arrow class="tc-nav" @click-left="$router.back()" />

    <div class="about-hero">
      <div class="about-logo">🚩</div>
      <div class="about-name">田冲助农商城</div>
      <div class="about-sub">贵州亿田农业 · 田冲红色美丽乡村强村富民工坊</div>
    </div>

    <div class="section">
      <div class="section-head"><div class="section-title">工坊介绍</div></div>
      <div class="about-content">
        <p>
          田冲助农商城是田冲红色美丽乡村强村富民工坊旗下的助农电商平台，由贵州亿田农业倾力打造。平台坚持<strong>党建引领、乡村振兴</strong>的发展思路，把党组织建在产业链上、把工坊办在家门口，让田冲村的绿水青山通过一根根网线走出大山。
        </p>
        <p>
          我们与本地农户、合作社深度合作，从田间地头直采直发，省去中间环节，让农户卖得舒心、消费者买得放心。每一笔订单，都是一次对乡村振兴实实在在的支持。
        </p>
        <p>
          平台设立<strong>红色助农专区</strong>，优先推广脱贫户、监测户农产品；设立<strong>党员推荐</strong>专栏，由党员示范户为品质背书，让"党旗红"与"田园绿"交相辉映。
        </p>
      </div>
    </div>

    <div class="section">
      <div class="section-head"><div class="section-title">我们的使命</div></div>
      <div class="about-missions">
        <div class="about-mission">
          <span class="about-mission-icon">🌾</span>
          <span>产地直采</span>
          <em>田间到餐桌，新鲜看得见</em>
        </div>
        <div class="about-mission">
          <span class="about-mission-icon">🚩</span>
          <span>红色助农</span>
          <em>优先帮扶脱贫户农产品</em>
        </div>
        <div class="about-mission">
          <span class="about-mission-icon">🤝</span>
          <span>强村富民</span>
          <em>集体经济壮大，村民增收</em>
        </div>
        <div class="about-mission">
          <span class="about-mission-icon">💚</span>
          <span>品质保障</span>
          <em>党员示范户品质背书</em>
        </div>
      </div>
    </div>

    <div class="section about-slogan-block">
      <div class="about-slogan">党建引领 · 强村富民</div>
      <div class="about-slogan-sub">消费帮扶，一起为乡村振兴助力</div>
    </div>

    <div class="about-foot">© {{ year }} 贵州亿田农业 田冲红色美丽乡村强村富民工坊</div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const year = ref(new Date().getFullYear())
</script>

<style scoped>
.about-hero {
  padding: 36px 20px 28px;
  background: linear-gradient(160deg, #e63946 0%, #c1121f 100%);
  color: #fff;
  text-align: center;
}
.about-logo {
  font-size: 46px;
}
.about-name {
  margin-top: 8px;
  font-size: 21px;
  font-weight: 800;
  letter-spacing: 2px;
}
.about-sub {
  margin-top: 6px;
  font-size: 12px;
  opacity: 0.9;
}
.about-content {
  padding: 0 14px 14px;
  font-size: 14px;
  line-height: 22px;
  color: #44464d;
}
.about-content p {
  margin: 0 0 10px;
}
.about-content strong {
  color: #e63946;
}
.about-missions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  padding: 0 12px 14px;
}
.about-mission {
  background: #fafafa;
  border-radius: 10px;
  padding: 14px 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 14px;
  font-weight: 600;
}
.about-mission-icon {
  font-size: 26px;
}
.about-mission em {
  font-style: normal;
  font-size: 11px;
  color: #9a9da5;
  font-weight: 400;
}
.about-slogan-block {
  padding: 24px 12px;
  text-align: center;
  background: linear-gradient(135deg, #fff7f2, #fffbf5);
}
.about-slogan {
  font-size: 20px;
  font-weight: 800;
  color: #c1121f;
  letter-spacing: 4px;
}
.about-slogan-sub {
  margin-top: 6px;
  font-size: 12px;
  color: #d48806;
}
.about-foot {
  text-align: center;
  color: #b0b3ba;
  font-size: 11px;
  padding: 14px 0 24px;
}
</style>
