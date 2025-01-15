<script setup>
import { onMounted, ref } from "vue";
import axios from "axios";

const facility_schedules_1 = ref([]);
const facility_schedules_2 = ref([]);

// Start of Selection
const schedule_now_1 = ref(null);
const schedule_now_2 = ref(null);

const pickNowSchedule = () => {
  console.log("pickNowSchedule実行");
  // 施設ごとに現在適用中のスケジュールにフラグを立てる
  const now = new Date();

  facility_schedules_1.value.forEach((schedule) => {
    const startDate = new Date(schedule.start_date);
    const endDate = new Date(schedule.end_date);

    if (startDate <= now && now <= endDate) {
      schedule.now = 1;
      schedule_now_1.value = schedule;
    } else {
      schedule.now = 0;
    }

    if (endDate < now) {
      schedule.already = 1;
    }
  });

  facility_schedules_2.value.forEach((schedule) => {
    const startDate = new Date(schedule.start_date);
    const endDate = new Date(schedule.end_date);

    if (startDate <= now && now <= endDate) {
      schedule.now = 1;
      schedule_now_2.value = schedule;
    } else {
      schedule.now = 0;
    }

    if (endDate < now) {
      schedule.already = 1;
    }
  });
};
const getFacilitySchedule = () => {
  axios
    .get(route("getFacilitySchedule"))
    .then((res) => {
      console.log(res.data);
      facility_schedules_1.value = res.data.facility_schedules_1;
      facility_schedules_2.value = res.data.facility_schedules_2;
      pickNowSchedule();
    })
    .catch((error) => {
      console.log(error);
    });
};

// 一時間に一度スケジュールデータを取得(hh:03)
// const executeEveryHour = () => {
//   const now = new Date();
//   const delay =
//     (63 - now.getMinutes()) * 60000 -
//     now.getSeconds() * 1000 -
//     now.getMilliseconds();

//   setTimeout(() => {
//     getFacilitySchedule();
//     setInterval(getFacilitySchedule(), 3600000); // 3600000ミリ秒は1時間
//   }, delay);
// };

onMounted(() => {
  getFacilitySchedule();
  // executeEveryHour();

  setInterval(pickNowSchedule, 300000);
});
</script>
<template>
  <main id="main_container">
    <div class="room_1">
      <div class="content">
        <div class="top_content">
          <h1 class="facility_name">応接室</h1>
          <h2 :class="{ use_status: true, active: schedule_now_1 }">
            {{ schedule_now_1 ? "使用中" : "空室" }}
          </h2>
        </div>
        <div v-if="schedule_now_1" class="middle_content">
          <p class="datetime">
            {{
              new Date(schedule_now_1.start_date).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
              })
            }}
            -
            {{
              new Date(schedule_now_1.end_date).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
              })
            }}
          </p>
          <marquee class="title">{{ schedule_now_1.title }}</marquee>
          <p class="participants">
            <span>参加者:</span>
            <span
              class="user_name"
              v-for="participant in schedule_now_1.participants"
              :key="participant.id"
              >{{ participant.user_name }}</span
            >
          </p>
        </div>
        <div class="bottom_content">
          <section class="text-gray-600 body-font">
            <div class="container mx-auto">
              <div class="w-full mx-auto overflow-auto">
                <table class="table-auto w-full text-left whitespace-no-wrap">
                  <thead>
                    <tr>
                      <th
                        class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                      >
                        予定名称
                      </th>
                      <th
                        class="w-48 px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                      >
                        開始時間
                      </th>
                      <th
                        class="w-48 px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                      >
                        終了時間
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="facility in facility_schedules_1"
                      :key="facility.id"
                      :class="{
                        active: facility.now,
                        already: facility.already,
                      }"
                    >
                      <td class="px-4 py-6 font-bold">{{ facility.title }}</td>
                      <td class="datetime px-4 py-6 font-bold">
                        {{
                          new Date(facility.start_date).toLocaleTimeString([], {
                            hour: "2-digit",
                            minute: "2-digit",
                            hour12: false, // 24時間形式に設定
                          })
                        }}
                      </td>
                      <td class="datetime px-4 py-6 font-bold">
                        {{
                          new Date(facility.end_date).toLocaleTimeString([], {
                            hour: "2-digit",
                            minute: "2-digit",
                            hour12: false, // 24時間形式に設定
                          })
                        }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
    <div class="room_2">
      <div class="content">
        <div class="top_content">
          <h1 class="facility_name">社長室</h1>
          <h2 :class="{ use_status: true, active: schedule_now_2 }">
            {{ schedule_now_2 ? "使用中" : "空室" }}
          </h2>
        </div>
        <div v-if="schedule_now_2" class="middle_content">
          <p class="datetime">
            {{
              new Date(schedule_now_2.start_date).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
                hour12: false, // 24時間形式に設定
              })
            }}
            -
            {{
              new Date(schedule_now_2.end_date).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
                hour12: false, // 24時間形式に設定
              })
            }}
          </p>
          <marquee class="title">{{ schedule_now_2.title }}</marquee>
          <p class="participants">
            <span>参加者:</span>
            <span
              class="user_name"
              v-for="participant in schedule_now_2.participants"
              :key="participant.id"
              >{{ participant.user_name }}</span
            >
          </p>
        </div>
        <div class="bottom_content">
          <section class="text-gray-600 body-font">
            <div class="container mx-auto">
              <div class="w-full mx-auto overflow-auto">
                <table class="table-auto w-full text-left whitespace-no-wrap">
                  <thead>
                    <tr>
                      <th
                        class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl"
                      >
                        タイトル
                      </th>
                      <th
                        class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                      >
                        開始時間
                      </th>
                      <th
                        class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100"
                      >
                        終了時間
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="facility in facility_schedules_2"
                      :key="facility.id"
                      :class="{
                        active: facility.now,
                        already: facility.already,
                      }"
                    >
                      <td class="px-4 py-6 font-bold">{{ facility.title }}</td>
                      <td class="datetime px-4 py-6 font-bold">
                        {{
                          new Date(facility.start_date).toLocaleTimeString([], {
                            hour: "2-digit",
                            minute: "2-digit",
                          })
                        }}
                      </td>
                      <td class="datetime px-4 py-6 font-bold">
                        {{
                          new Date(facility.end_date).toLocaleTimeString([], {
                            hour: "2-digit",
                            minute: "2-digit",
                          })
                        }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </main>
</template>
<style lang="scss" scoped>
#main_container {
  // font-family: "IPAexGothic", "Noto Sans CJK JP", "Arial", sans-serif;
  width: 100vw;
  height: 100vh;
  background-color: rgba(224, 224, 224, 0.733);
  display: flex;
  justify-content: space-around;
  align-items: start;

  // padding: 10px;
  & > div {
    width: 48%;
    height: 100%;

    box-sizing: border-box;
    padding: 0 12px;
    & .content {
      position: relative;
      height: 100%;
      width: 100%;

      & .top_content {
        position: absolute;
        top: 2%;
        width: 100%;

        & .facility_name {
          font-size: 80px;
          font-weight: bold;
          margin: 10px 0;
          color: rgb(75, 75, 75);
        }
        & .use_status {
          font-size: 68px;
          width: 100%;
          background-color: rgb(20, 219, 79);
          text-align: center;
          font-weight: bold;
          color: white;
          border-radius: 4px;
          padding: 8px 0;

          &.active {
            background-color: rgb(255, 43, 107);
          }
        }
      }

      & .middle_content {
        position: absolute;
        top: 32%;
        width: 100%;

        & .datetime {
          font-size: 40px;
          color: rgb(75, 75, 75);
          font-weight: bold;
        }
        & .title {
          font-size: 60px;
          color: rgb(255, 43, 114);
          background-color: white;
          padding-left: 1em;
          font-weight: bold;
          max-width: 100%;
          overflow: hidden;
          white-space: nowrap;
        }

        & .participants {
          font-weight: bold;
          display: flex;
          flex-wrap: wrap;

          & span:first-child {
            margin-top: 6px;
          }

          & .user_name {
            font-size: 40px;
            margin-left: 10px;
            color: rgb(255, 43, 114);
          }
        }
      }

      & .bottom_content {
        position: absolute;
        top: 58%;
        width: 100%;

        & .active {
          border: 4px solid rgb(255, 43, 114);
          box-sizing: border-box;
        }
        & .already {
          opacity: 0.6;
          background-color: rgb(202, 202, 202);
        }

        & table {
          font-size: 20px;

          & td {
            &.datetime {
              font-size: 40px;
            }
          }
        }
      }
    }
  }
}
</style>