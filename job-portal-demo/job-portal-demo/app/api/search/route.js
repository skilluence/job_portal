import { NextResponse } from "next/server";

const APIFY_TOKEN = process.env.APIFY_API_TOKEN;
const ACTOR_RUN_URL = "https://api.apify.com/v2/acts/fantastic-jobs~advanced-linkedin-job-search-api/runs";
const DATASET_ITEMS_BASE_URL = "https://api.apify.com/v2/datasets";

async function waitForRun(runId) {
  for (let i = 0; i < 25; i++) {
    const statusRes = await fetch(
      `https://api.apify.com/v2/actor-runs/${runId}?token=${APIFY_TOKEN}`
    );
    if (!statusRes.ok) {
      throw new Error(`Status check failed (${statusRes.status})`);
    }
    const runInfo = await statusRes.json();
    const status = runInfo?.data?.status;
    if (status === "SUCCEEDED") {
      return runInfo.data.defaultDatasetId;
    }
    if (["FAILED", "ABORTED", "TIMED-OUT"].includes(status)) {
      throw new Error(`Job search run ${status.toLowerCase()}. Please try again.`);
    }
    await new Promise((resolve) => setTimeout(resolve, 2000));
  }
  throw new Error("Search timed out after 50 seconds. Please try again.");
}

export async function POST(request) {
  if (!APIFY_TOKEN) {
    return NextResponse.json({ error: "APIFY_API_TOKEN is not configured." }, { status: 500 });
  }

  const body = await request.json();
  const query = String(body?.query || "").trim();
  if (!query) {
    return NextResponse.json({ error: "Missing query parameter." }, { status: 400 });
  }

  const payload = {
    limit: 100,
    includeAi: true,
    descriptionType: "html",
    titleSearch: [query],
    locationSearch: ["United States"],
  };

  try {
    const runResponse = await fetch(`${ACTOR_RUN_URL}?token=${APIFY_TOKEN}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    if (!runResponse.ok) {
      throw new Error(`Unable to start search (${runResponse.status}).`);
    }

    const runData = await runResponse.json();
    const runId = runData?.data?.id;
    if (!runId) {
      throw new Error("Could not initialize Apify actor run.");
    }

    const datasetId = await waitForRun(runId);
    const itemsResponse = await fetch(
      `${DATASET_ITEMS_BASE_URL}/${datasetId}/items?token=${APIFY_TOKEN}&clean=true&format=json`
    );

    if (!itemsResponse.ok) {
      throw new Error(`Failed to fetch results (${itemsResponse.status}).`);
    }

    const items = await itemsResponse.json();
    return NextResponse.json({ items });
  } catch (error) {
    return NextResponse.json({ error: error.message || "Unexpected search error." }, { status: 500 });
  }
}
