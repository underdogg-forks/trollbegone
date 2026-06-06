import { execFileSync } from 'node:child_process';

export type SeedContext = {
  userEmail: string;
  userPassword: string;
  accountId: string;
  secondAccountId: string;
};

const runPhp = (command: string): string => execFileSync('php', ['artisan', command], { encoding: 'utf8' }).trim();

export const seedBaseData = (): SeedContext => {
  runPhp('migrate:fresh --force');

  const json = runPhp('e2e:seed');

  let parsed: any;
  try {
    parsed = JSON.parse(json);
  } catch (error) {
    throw new Error(`Failed to parse seed data JSON: ${json}`);
  }

  const requiredKeys = ['email', 'password', 'account_id', 'account_id_2'];
  for (const key of requiredKeys) {
    if (!(key in parsed) || parsed[key] === null || parsed[key] === undefined) {
      throw new Error(`Seed data missing required key: ${key}. Received: ${JSON.stringify(parsed)}`);
    }
  }

  return {
    userEmail: parsed.email,
    userPassword: parsed.password,
    accountId: String(parsed.account_id),
    secondAccountId: String(parsed.account_id_2),
  };
};
