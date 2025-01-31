<script setup>

</script>

<template>
  <div v-if="$page.props.flash.message">
    <div
      :class="[
        'border-t-4 rounded-b px-4 py-3 shadow-md',
        {
          'bg-gray-100 border-gray-500 text-gray-900':
            $page.props.flash.status === 'info',
          'bg-red-100 border-red-500 text-red-900':
            $page.props.flash.status === 'error',
          'bg-teal-100 border-teal-500 text-teal-900':
            $page.props.flash.status === 'success',
        },
      ]"
      role="alert"
    >
      <div class="flex">
        <div class="py-1">
          <svg
            :class="[
              'fill-current h-6 w-6 mr-4',
              {
                'text-gray-500': $page.props.flash.status === 'info',
                'text-red-500': $page.props.flash.status === 'error',
                'text-teal-500': $page.props.flash.status === 'success',
              },
            ]"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
          >
            <path
              d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"
            />
          </svg>
        </div>
        <div>
          <p
            :class="[
              'font-semibold',
              {
                'text-gray-500': $page.props.flash.status === 'info',
                'text-teal-500': $page.props.flash.status === 'success',
                'text-red-500': $page.props.flash.status === 'error',
              },
            ]"
          >
            {{
              $page.props.flash.status == "info"
                ? "お知らせ"
                : $page.props.flash.status == "success"
                ? "成功しました。"
                : "エラーが発生しました。"
            }}
          </p>
          <p class="text-sm font-serif">
            {{ $page.props.flash.message }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
  