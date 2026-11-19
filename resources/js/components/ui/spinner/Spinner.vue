<script setup lang="ts">
import { useUiI18n } from "@/composables/useUiI18n";

import type { HTMLAttributes } from "vue"
import { Loader2Icon } from "lucide-vue-next"
import { cn } from "@/lib/utils"

const { t } = useUiI18n();

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <Loader2Icon
    role="status"
    :aria-label="t('common.loading')"
    :class="cn('size-4 animate-spin', props.class)"
  />
</template>
