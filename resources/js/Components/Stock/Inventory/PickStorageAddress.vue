<script setup>
import { onMounted, ref, reactive } from "vue";
import axios from "axios";
const props = defineProps({
  quantity: Number,
});
const locations = ref([]);
const storage_addresses = ref([]);

const location = reactive({
  location_id: null,
  storage_address_id: null,
  quantity: null,
});

const emit = defineEmits(["updateLocation"]);
const updateLocation = () => {
  // 入力チェック

  const selectedLocation = locations.value.find(
    (loc) => loc.id === location.location_id
  );

  const selectedAddress = storage_addresses.value.find(
    (addr) => addr.id === location.storage_address_id
  );

  if (
    confirm(
      `下記の内容で登録します。よろしいですか？\n格納先:${selectedLocation.name}\nアドレス:${selectedAddress.address}\n個数:${location.quantity}\n`
    )
  ) {
    emit("updateLocation", {
      storage_address_id: location.storage_address_id,
      quantity: location.quantity,
    });
  }
};

const checkQuantity = (new_quantity) => {
  if (props.quantity && props.quantity < new_quantity) {
    alert(
      `元の個数より大きい数量を登録することはできません。\n上限値：${props.quantity}`
    );
    location.quantity = props.quantity;
  }
};
const getLocations = () => {
  axios
    .get(route("stock.getLocations"))
    .then((res) => {
      locations.value = res.data;
    })
    .catch((error) => {
      console.log(error);
    });
};
const getStorageAddresses = ($location_id) => {
  if ($location_id) {
    axios
      .get(route("stock.getStorageAddresses", { location_id: $location_id }))
      .then((res) => {
        storage_addresses.value = res.data;
        console.log(res.data);
      })
      .catch((error) => {
        console.log(error);
      });
  }
};

onMounted(() => {
  getLocations();

  location.quantity = props.quantity ?? 0;
});
</script>
<template>
  <div class="flex items-center justify-start py-2 mb-2">
    <label class="w-1/3 mr-1" for="">
      <span class="text-gray-600 text-sm mb-1">格納先</span>
      <select
        @change="getStorageAddresses($event.target.value)"
        name=""
        id="select_location_id"
        v-model="location.location_id"
        class="w-full appearance-none block bg-gray-50 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
      >
        <option value="" disabled selected>格納先を選択してください。</option>
        <option
          v-for="location in locations"
          :key="location.id"
          :value="location.id"
        >
          {{ location.name }}
        </option>
      </select>
    </label>

    <label v-if="location.location_id" for="" class="w-1/3 mr-1">
      <span class="text-gray-600 text-sm mb-1">アドレス</span>
      <select
        v-model="location.storage_address_id"
        name=""
        id="select_storage_address"
        class="w-full appearance-none block bg-gray-50 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
      >
        <option value="0">アドレスを選択してください。</option>
        <option
          v-for="storage_address in storage_addresses"
          :key="storage_address.id"
          :value="storage_address.id"
        >
          {{ storage_address.address }}
        </option>
      </select>
    </label>

    <label v-if="location.storage_address_id" for="" class="w-1/3">
      <span class="text-gray-600 text-sm mb-1">個数</span>
      <input
        @change="checkQuantity($event.target.value)"
        type="number"
        class="w-full appearance-none block bg-gray-50 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500 text-center"
        v-model="location.quantity"
      />
    </label>
  </div>

  <button
    v-if="
      location.location_id && location.storage_address_id && location.quantity
    "
    @click="updateLocation"
    class="text-sm bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
  >
    確定
  </button>
</template>
<style scope lang="scss">
</style>
