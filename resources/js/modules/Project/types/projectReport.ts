export type ProjectReportChart =
    | {
          kind: 'grouped-bars';
          categories: string[];
          series: { name: string; values: number[] }[];
      }
    | { kind: 'bars'; labels: string[]; values: number[] }
    | { kind: 'donut'; labels: string[]; values: number[] };

export type ProjectReportBlock = {
    slug: string;
    title: string;
    subtitle: string;
    summary: { label: string; value: string }[];
    headers: string[];
    rows: string[][];
    chart?: ProjectReportChart;
};
