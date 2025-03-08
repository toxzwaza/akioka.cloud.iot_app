<script setup>
import { DotLottieVue } from '@lottiefiles/dotlottie-vue'
import { onMounted,ref,reactive } from "vue"
import axios from "axios"

const users = ref([])

const getUsers = () => {
axios.get(route('lunch.getUsers'))
.then(res => {
    console.log(res.data)
    users.value = res.data
})
.catch(error => {
    console.log(error)
})
}

const available_scan = ref(false)
const form = reactive({
    user_id: null,
    user_name: null,
    order_flg: null,
    receive_flg: null
})
const handleChangeUserId = () => {
    if(form.user_id){
        const user = users.value.find(user => user.id == form.user_id)
        console.log(user)
    }else{
     scan_input_available()   
    }
}
const scan_input_available = () =>{
    const qr_reader_input = document.querySelector('#qr-reader-input')
    if (document.activeElement !== qr_reader_input) {
        qr_reader_input.focus()
        available_scan.value = true 
    }
}
onMounted( () => {
    getUsers()
    scan_input_available()

})

</script>
<template>
  <main class="p-8">
    <div class="flex justify-between items-start">
      <div id="left_container" class="w-1/3">
      <h2 class="my-8 text-xl font-bold text-gray-600">弁当注文状況</h2>
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
          <table
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400"
          >
            <thead
              class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
            >
              <tr>
                <th scope="col" class="px-6 py-3">時間</th>
                <th scope="col" class="px-6 py-3">名前</th>
              </tr>
            </thead>
            <tbody>
              <tr
                class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200"
              >
                <td class="px-6 py-4">Laptop</td>
                <td class="px-6 py-4">$2999</td>
              </tr>
              <tr
                class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200"
              >
                <td class="px-6 py-4">Laptop</td>
                <td class="px-6 py-4">$2999</td>
              </tr>
              <tr
                class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200"
              >
                <td class="px-6 py-4">Laptop</td>
                <td class="px-6 py-4">$2999</td>
              </tr>

            </tbody>
          </table>
        </div>
      </div>
      <div  id="right_container" class="w-2/3 py-8 pl-8">
      <input v-model="form.user_id" type="number" name="" id="qr-reader-input" class="opacity-100" @change="handleChangeUserId">
      <div v-if="available_scan">

        <h1 class="text-center font-bold text-4xl text-red-500 ">QRコードをスキャンしてください</h1>
<DotLottieVue class="mx-auto" style="height: 500px; width: 500px" autoplay loop src="https://lottie.host/061aefbf-c9fb-4081-bd58-fb534a5e2c9c/WwBjZin5yr.lottie" />
      </div>

        <div id="button_content" class="mt-12">
            <h2 class="text-xl font-bold text-gray-600">本日のポット当番</h2>
        </div>
      </div>
    </div>
  </main>
</template>
<style scoped lang="scss">
</style>