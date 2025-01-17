<script setup>
</script>
<template>
  <div class="data_card">
    <h2 class="place_name">電気炉</h2>
    <div class="">
      <div class="data_content">
        <img class="icon" src="" alt="" />

        <div class="data">
          <span class="value">20</span>
          <span class="util">℃</span>
        </div>
      </div>
      <div class="data_content">
        <img class="icon" src="" alt="" />

        <div class="data">
          <span class="value">60</span>
          <span class="util">%</span>
        </div>
      </div>
      <div class="data_content">
        <img class="icon" src="" alt="" />

        <div class="data">
          <span class="value">800</span>
          <span class="util">PPM</span>
        </div>
      </div>
    </div>

    <div class="log_button">
      <span>Data Log</span>
      <i class="fas fa-arrow-right"></i>
    </div>
  </div>
</template>
<style lang="scss" scoped>
.data_card {
  color: white;
  background-color: #2aaabe;
  min-width: 18%;
  max-width: 280px;
  box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
  border-radius: 3px;
  padding: 1%;
  margin-right: 2%;

  & .place_name {
    font-size: 0.9rem;
    margin-bottom: 1rem;
    font-weight: bold;
  }
  & .data_content {
    &:nth-child(1),
    &:nth-child(2) {
      border-bottom: 2px dashed white;
    }

    display: flex;
    justify-content: space-between;
    padding: 2% 0;
    margin: 2% 0;

    & .icon {
      width: 50px;
      height: 50px;
      background-color: rgb(255, 255, 255);
    }
    & .data {
      margin: 1% 0;

      width: 44%;
      display: flex;
      justify-content: space-around;
      align-items: start;

      & .value {
        font-weight: bold;
        font-size: 2rem;
        width: 60%;
        text-align: right;
      }
      & .util {
        font-size: 0.8rem;
        margin-top: 0.6rem;
        width: 30%;
        text-align: left;
      }
    }
  }

  & .log_button {
    background-color: #1d93a0;
    text-align: center;
    padding: 1% 0;
    font-weight: bold;
    display: flex;
    justify-items: center;
    align-items: center;
    justify-content: center;
    border-radius: 3px;

    & span {
      margin-right: 2%;
    }
  }
}
</style>
